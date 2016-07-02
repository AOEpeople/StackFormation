<?php

namespace StackFormation\ValueResolver\Stage;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class Md5 extends AbstractValueResolverStage
{

    public function invoke($string)
    {
        $string = preg_replace_callback(
            '/\{md5:([^:\}\{]+?)\}/',
            function ($matches) {
                $file = $matches[1];
                $cwd = getcwd();
                if ($this->sourceBlueprint) {
                    chdir($this->sourceBlueprint->getBasePath());
                }
                if (!is_file($file)) {
                    throw new FileNotFoundException("File '$file' not found.");
                }
                $md5 = md5_file($file);
                chdir($cwd);
                return $md5;
            },
            $string
        );
        return $string;
    }

}
