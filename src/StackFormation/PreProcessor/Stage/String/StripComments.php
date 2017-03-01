<?php

namespace StackFormation\PreProcessor\Stage\String;

use StackFormation\PreProcessor\Stage\AbstractStringPreProcessorStage;

class StripComments extends AbstractStringPreProcessorStage {

    /**
     * @param string $content
     * @return string
     */
    public function invoke($content)
    {
        // there's a problem with '"http://example.com"' being converted to '"http:'
        // $content = preg_replace('~//[^\r\n]*|/\*.*?\*/~s', '', $content);

        // there's a problem with "arn:aws:s3:::my-bucket/*"
        // $content = preg_replace('~/\*.*?\*/~s', '', $content);

        // quick workaround: don't allow quotes
        return preg_replace('~/\*[^"]*?\*/~s', '', $content);
    }
}
