<?php

namespace StackFormation;

class Preprocessor {

    public function process($filepath) {
        if (!is_file($filepath)) {
            throw new \Exception('File not found');
        }
        $json = file_get_contents($filepath);

        $json = $this->injectFilecontent($json, dirname($filepath));
        $json = $this->replaceRef($json);
        $json = $this->replaceMarkers($json);
        return $json;
    }

    public function replaceMarkers($json) {
        $markers = array(
            '###TIMESTAMP###' => date(\DateTime::ISO8601),
        );
        return str_replace(array_keys($markers), array_values($markers), $json);
    }

    public function injectFilecontent($jsonString, $basePath) {
        return preg_replace_callback('/(\s*)(.*){\s*"Fn::FileContent"\s*:\s*"(.+?)"\s*}/', function(array $matches) use ($basePath) {
            $file = $basePath . '/' . $matches[3];
            if (!is_file($file)) {
                throw new \Exception("File $file not found");
            }

            $fileContent = file_get_contents($file);
            $fileContent = $this->injectInclude($fileContent, dirname(realpath($file)));

            $lines = explode("\n", $fileContent);
            foreach ($lines as &$line) {
                $line .= "\n";
            }

            $result = ' {"Fn::Join": ["", ' . json_encode($lines, JSON_PRETTY_PRINT) . ']}';

            $whitespace = trim($matches[1], "\n");
            $result = str_replace("\n", "\n".$whitespace, $result);

            return $matches[1] . $matches[2] . $result;
        }, $jsonString);
    }

    public function injectInclude($string, $basePath) {
        return preg_replace_callback('/###INCLUDE:(.+)/', function(array $matches) use ($basePath) {
            $file = $basePath . '/' . $matches[1];
            if (!is_file($file)) {
                throw new \Exception("File $file not found");
            }

            $fileContent = file_get_contents($file);

            $fileContent = preg_replace('/\#\!.*/', '', $fileContent);
            $fileContent = trim($fileContent);

            return $fileContent;
        }, $string);
    }

    public function replaceRef($jsonString) {
        return preg_replace('/\{\s*Ref\s*:\s*([a-zA-Z0-9:]+?)\s*\}/', '", {"Ref": "$1"}, "', $jsonString);
    }

}