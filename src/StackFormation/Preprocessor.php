<?php

namespace StackFormation;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class Preprocessor
{
    const MAX_JS_FILE_INCLUDE_SIZE = 4096;

    public function processJson($json, $basePath)
    {
        if (!is_string($json)) {
            throw new \InvalidArgumentException('Expected json string');
        }
        // TODO: refactor to use a pipeline
        $json = $this->stripComments($json);
        $json = $this->parseRefInDoubleQuotedStrings($json);
        $json = $this->expandPort($json);
        $json = $this->injectFilecontent($json, $basePath);
        $json = $this->split($json);
        $json = $this->replaceFnGetAttr($json);
        $json = $this->replaceRef($json);
        $json = $this->replaceMarkers($json);
        return $json;
    }
    
    protected function stripComments($json)
    {
        // there's a problem with '"http://example.com"' being converted to '"http:'
        // $json = preg_replace('~//[^\r\n]*|/\*.*?\*/~s', '', $json);

        // there's a problem with "arn:aws:s3:::my-bucket/*"
        // $json = preg_replace('~/\*.*?\*/~s', '', $json);

        // quick workaround: don't allow quotes
        $json = preg_replace('~/\*[^"]*?\*/~s', '', $json);
        return $json;
    }

    protected function parseRefInDoubleQuotedStrings($json)
    {
        $json = preg_replace_callback(
            '/"([^"]*){Ref:(.+?)}([^"]*)"/',
            function ($matches) {
                $snippet = $matches[0];
                $snippet = trim($snippet, '"');
                $pieces = preg_split('/({Ref:.+})/U', $snippet, -1, PREG_SPLIT_DELIM_CAPTURE);
                $processedPieces = [];
                foreach ($pieces as $piece) {
                    if (empty($piece)) {
                        continue;
                    }
                    if (substr($piece, 0, 5) == '{Ref:') {
                        $processedPieces[] = preg_replace('/{Ref:(.+)}/', '{"Ref":"$1"}', $piece);
                    } else {
                        $processedPieces[] = '"' . $piece . '"';
                    }
                }
                return '{"Fn::Join": ["", [' . implode(', ', $processedPieces) . ']]}';
            },
            $json
        );
        return $json;
    }

    protected function replaceMarkers($json)
    {
        $markers = [
            '###TIMESTAMP###' => date(\DateTime::ISO8601),
        ];
        $json = str_replace(array_keys($markers), array_values($markers), $json);

        $json = preg_replace_callback(
            '/###ENV:([^#:]+)###/',
            function ($matches) {
                if (!getenv($matches[1])) {
                    throw new \Exception("Environment variable '{$matches[1]}' not found");
                }

                return getenv($matches[1]);
            },
            $json
        );

        return $json;
    }

    protected function expandPort($jsonString)
    {
        return preg_replace('/([\{,]\s*)"Port"\s*:\s*"(\d+)"/', '\1"FromPort": "\2", "ToPort": "\2"', $jsonString);
    }

    protected function injectFilecontent($jsonString, $basePath)
    {
        $jsonString = preg_replace_callback(
            '/(\s*)(.*){\s*"Fn::FileContent(Unpretty|TrimLines|Minify)?"\s*:\s*"(.+?)"\s*}/',
            function (array $matches) use ($basePath) {
                $file = $basePath . '/' . end($matches);
                if (!is_file($file)) {
                    throw new FileNotFoundException("File '$file' not found");
                }

                $fileContent = file_get_contents($file);
                $fileContent = $this->injectInclude($fileContent, dirname(realpath($file)));
                $ext = pathinfo($file, PATHINFO_EXTENSION);

                if ($matches[3] == 'Minify' && $ext != 'js') {
                    throw new \Exception('Fn::FileContentMinify is only supported for *.js files. (File: ' . $file . ')');
                }

                if ($ext === 'js') {
                    if ($matches[3] == 'Minify') {
                        $fileContent = \JShrink\Minifier::minify($fileContent, ['flaggedComments' => false]);
                    }

                    $size = strlen($fileContent);
                    if ($size > self::MAX_JS_FILE_INCLUDE_SIZE) {
                        // this is assuming you are uploading an inline JS file to AWS Lambda
                        throw new \Exception(sprintf("JS file is larger than %s bytes (actual size: %s bytes)", self::MAX_JS_FILE_INCLUDE_SIZE, $size));
                    }
                }

                $fileContent = preg_replace_callback(
                    '/###JSON###(.+?)######/',
                    function (array $m) {
                        return "###JSON###" . base64_encode($m[1]) . "######";
                    },
                    $fileContent
                );

                $lines = explode("\n", $fileContent);
                foreach ($lines as $key => &$line) {
                    if ($matches[3] == 'TrimLines') {
                        $line = trim($line);
                        if (empty($line)) {
                            unset($lines[$key]);
                        }
                    }
                    $line .= "\n";
                }

                if ($matches[3] == 'Unpretty') {
                    $result = ' {"Fn::Join": ["", ' . json_encode(array_values($lines)) . ']}';
                } else {
                    $result = ' {"Fn::Join": ["", ' . json_encode(array_values($lines), JSON_PRETTY_PRINT) . ']}';
                }

                $whitespace = trim($matches[1], "\n");
                $result = str_replace("\n", "\n" . $whitespace, $result);

                return $matches[1] . $matches[2] . $result;
            },
            $jsonString
        );

        $jsonString = preg_replace_callback(
            '/###JSON###(.+?)######/',
            function (array $m) {
                return '", ' . base64_decode($m[1]) . ', "';
            },
            $jsonString
        );

        return $jsonString;
    }

    protected function split($jsonString)
    {
        return preg_replace_callback(
            '/(\s*)(.*){\s*"Fn::Split"\s*:\s*\[\s*"(.*?)"\s*,\s*"(.*?)"\s*\]\s*}/',
            function (array $matches) {
                if (empty($matches[3])) {
                    throw new \Exception('Delimiter cannot be empty');
                }
                if (empty($matches[4])) {
                    throw new \Exception('String cannot be empty');
                }
                $pieces = explode($matches[3], $matches[4]);
                return $matches[1] . $matches[2] . '["' . implode('", "', $pieces).'"]';
            },
            $jsonString
        );
    }

    protected function injectInclude($string, $basePath)
    {
        return preg_replace_callback(
            '/###INCLUDE:(.+)/',
            function (array $matches) use ($basePath) {
                $file = $basePath . '/' . $matches[1];
                if (!is_file($file)) {
                    throw new FileNotFoundException("File $file not found");
                }

                $fileContent = file_get_contents($file);
                $fileContent = trim($fileContent);

                return $fileContent;
            },
            $string
        );
    }

    protected function replaceRef($jsonString)
    {
        return preg_replace('/\{\s*Ref\s*:\s*([a-zA-Z0-9:]+?)\s*\}/', '", {"Ref": "$1"}, "', $jsonString);
    }

    /**
     * transforms {Fn::GetAtt:[resource,attribute]} to inline statement
     *
     * @param $jsonstring
     * @return mixed
     */
    protected function replaceFnGetAttr($jsonstring){
        return preg_replace('/\{\s*Fn::GetAtt:\[\s*([a-zA-Z0-9:]+?)\s*,\s*([a-zA-Z0-9:]+?)\s*\]\}/',
            '", {"Fn::GetAtt": ["$1", "$2"]} ,"', $jsonstring);
    }
}
