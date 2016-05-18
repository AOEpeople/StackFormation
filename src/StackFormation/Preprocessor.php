<?php

namespace StackFormation;

class Preprocessor
{

    public function process($filepath)
    {
        if (!is_file($filepath)) {
            throw new \Exception("File '$filepath' not found");
        }
        $json = file_get_contents($filepath);

        try {
            $json = $this->stripComments($json);
            $json = $this->expandPort($json);
            $json = $this->injectFilecontent($json, dirname($filepath));
            $json = $this->replaceRef($json);
            $json = $this->replaceMarkers($json);
        } catch(\Exception $e) {
            // adding some more information to the exception message
            throw new \Exception("Error processing $filepath ({$e->getMessage()})");
        }
        return $json;
    }
    
    public function stripComments($json)
    {
        // there's a problem with '"http://example.com"' being converted to '"http:'
        // $json = preg_replace('~//[^\r\n]*|/\*.*?\*/~s', '', $json);

        // there's a problem with "arn:aws:s3:::my-bucket/*"
        // $json = preg_replace('~/\*.*?\*/~s', '', $json);

        // quick workaround: don't allow quotes
        $json = preg_replace('~/\*[^"]*?\*/~s', '', $json);
        return $json;
    }

    public function replaceMarkers($json)
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

    public function expandPort($jsonString)
    {
        return preg_replace('/([\{,]\s*)"Port"\s*:\s*"(\d+)"/', '\1"FromPort": "\2", "ToPort": "\2"', $jsonString);
    }

    public function injectFilecontent($jsonString, $basePath)
    {
        $jsonString = preg_replace_callback(
            '/(\s*)(.*){\s*"Fn::FileContent(Unpretty|TrimLines|Minify)?"\s*:\s*"(.+?)"\s*}/',
            function (array $matches) use ($basePath) {
                $file = $basePath . '/' . end($matches);
                if (!is_file($file)) {
                    throw new \Exception("File $file not found");
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
                    if ($size > 2048) {
                        // this is assuming your uploading an inline JS file to AWS Lambda
                        throw new \Exception("JS file is larger than 2048 bytes (actual size: $size bytes)");
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

    public function injectInclude($string, $basePath)
    {
        return preg_replace_callback(
            '/###INCLUDE:(.+)/',
            function (array $matches) use ($basePath) {
                $file = $basePath . '/' . $matches[1];
                if (!is_file($file)) {
                    throw new \Exception("File $file not found");
                }

                $fileContent = file_get_contents($file);
                $fileContent = trim($fileContent);

                return $fileContent;
            },
            $string
        );
    }

    public function replaceRef($jsonString)
    {
        return preg_replace('/\{\s*Ref\s*:\s*([a-zA-Z0-9:]+?)\s*\}/', '", {"Ref": "$1"}, "', $jsonString);
    }
}
