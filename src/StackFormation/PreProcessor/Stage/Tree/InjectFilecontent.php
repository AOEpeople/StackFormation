<?php

namespace StackFormation\PreProcessor\Stage\Tree;

use StackFormation\PreProcessor\Stage\AbstractTreePreProcessorStage;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use StackFormation\PreProcessor\Rootline;
use StackFormation\PreProcessor\RootlineItem;
use StackFormation\PreProcessor\RecursiveArrayObject;

class InjectFilecontent extends AbstractTreePreProcessorStage
{
    const MAX_JS_FILE_INCLUDE_SIZE = 4096;

    /**
     * @param string $path
     * @param string $value
     * @param Rootline $rootLineReferences
     * @return bool
     */
    public function invoke($path, $value, Rootline $rootLineReferences)
    {
        $fnJoin = 'Fn::Join';
        $fnBase64 = 'Fn::Base64';

        if ((is_string($value) && preg_match('+Fn::FileContent(|Unpretty|TrimLines|Minify)(:)(.*)+', $value, $matches))) {

            $parentRootlineItem = $rootLineReferences->parent(1); /* @var RootlineItem $parentRootlineItem */
            $parent = $parentRootlineItem->getValue(); /* @var RecursiveArrayObject $parentData */
            $lines = $this->renderFileContent(trim(end($matches)), $matches[1]);

            $parent->offsetUnset($fnBase64);
            $parent[$fnBase64][$fnJoin] = ['', [implode('', $lines)]];

            return true;
        }

        if (preg_match('+Fn::FileContent(|Unpretty|TrimLines|Minify)$+', $path, $matches)) {

            $parentRootlineItem = $rootLineReferences->parent(1); /* @var RootlineItem $parentRootlineItem */
            $parent = $parentRootlineItem->getValue(); /* @var RecursiveArrayObject $parentData */
            $lines = $this->renderFileContent($value, $matches[1]);

            $parent->offsetUnset($matches[0]);
            $parent[$fnJoin] = ['', [implode('', $lines)]];

            return true;
        }

        return false;
    }

    /**
     * @param string $file
     * @param string $modus
     * @return array
     * @throws \Exception
     */
    protected function renderFileContent($file, $modus)
    {
        $file = $this->basePath . '/' . $file;
        if (!is_file($file)) {
            throw new FileNotFoundException("File '$file' not found");
        }

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if ($modus == 'Minify' && $ext != 'js') {
            throw new \Exception('Fn::FileContentMinify is only supported for *.js files. (File: ' . $file . ')');
        }

        $fileContent = file_get_contents($file);

        # TODO in own stage ?
        #$fileContent = $this->injectInclude($fileContent, dirname(realpath($file)));

        if ($ext === 'js') {
            if ($modus == 'Minify') {
                $fileContent = \JShrink\Minifier::minify($fileContent, ['flaggedComments' => false]);
            }

            $size = strlen($fileContent);
            if ($size > self::MAX_JS_FILE_INCLUDE_SIZE) {
                // this is assuming you are uploading an inline JS file to AWS Lambda
                throw new \Exception(sprintf("JS file is larger than %s bytes (actual size: %s bytes)", self::MAX_JS_FILE_INCLUDE_SIZE, $size));
            }
        }

        // TODO: this isn't optimal. Why are we processing this here in between?
        #$fileContent = $this->base64encodedJson($fileContent);

        $lines = explode("\n", $fileContent);
        foreach ($lines as $lineKey => &$line) {
            if ($modus == 'TrimLines') {
                $line = trim($line);
                if (empty($line)) {
                    unset($lines[$lineKey]);
                }
            }
            $line .= "\n";
        }

        #$whitespace = trim($matches[1], "\n");
        #$result = str_replace("\n", "\n" . $whitespace, $result);

        return $lines;
    }
}
