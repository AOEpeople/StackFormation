<?php

namespace StackFormation;

class TemplateMerger
{

    public function merge(array $templates, $description)
    {
        if (count($templates) == 0) {
            throw new \InvalidArgumentException('No templates given');
        }

        $mergedTemplate = [
            'AWSTemplateFormatVersion' => '2010-09-09',
            'Description' => $description
        ];

        $topLevelKeys = [
            'Parameters',
            'Mappings',
            'Conditions',
            'Resources',
            'Outputs',
            'Metadata'
        ];

        foreach ($templates as $file => $template) {
            $array = json_decode($template, true);
            if ($array['AWSTemplateFormatVersion'] != '2010-09-09') {
                throw new \Exception('Invalid AWSTemplateFormatVersion');
            }
            foreach ($topLevelKeys as $topLevelKey) {
                if (isset($array[$topLevelKey])) {
                    foreach ($array[$topLevelKey] as $key => $value) {
                        if (isset($mergedTemplate[$topLevelKey][$key])) {
                            throw new \Exception("Duplicate key '$key' found in '$topLevelKey' in file '$file'");
                        }
                        $mergedTemplate[$topLevelKey][$key] = $value;
                    }
                }
            }
        }
        return json_encode($mergedTemplate, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

}
