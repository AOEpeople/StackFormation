<?php

namespace StackFormation;

class TemplateMerger
{

    public function merge(array $templates, $description = null)
    {
        if (count($templates) == 0) {
            throw new \InvalidArgumentException('No templates given');
        }

        $mergedTemplate = [
            'AWSTemplateFormatVersion' => '2010-09-09',
            'Description'              => 'Merged Template',
        ];

        $topLevelKeys = [
            'Parameters',
            'Mappings',
            'Conditions',
            'Resources',
            'Outputs',
            'Metadata',
        ];

        // If we have no description and this is a single template, use the single template's description
        if (empty($description) && count($templates) === 1) {
            $template = reset($templates);
            if (!empty($template['Description'])) {
                $description = $template['Description'];
            }
        }

        foreach ($templates as $key => $template) {
            $prefix = '';

            if (!is_int($key)) {
                $prefix = $key;

                // Update all { "Ref": "..." }
                $template = preg_replace_callback(
                    '/\{\s*"Ref"\s*:\s*"([a-zA-Z0-9:]+?)"\s*\}/',
                    function ($matches) use ($prefix) {
                        return '{"Ref":"' . $prefix . $matches[1] . '"}';
                    },
                    $template
                );

                // Update all { "DependsOn": "..." }
                $template = preg_replace_callback(
                    '/\"DependsOn"\s*:\s*"([a-zA-Z0-9:]+?)"/',
                    function ($matches) use ($prefix) {
                        return '"DependsOn":"' . $prefix . $matches[1] . '"';
                    },
                    $template
                );

                // Update all { "DependsOn": ["...", "...", ...] }
                $template = preg_replace_callback(
                    '/\"DependsOn"\s*:\s*\[(.*)\]/s',
                    function ($matches) use ($prefix) {
                        $dependencies = $matches[1];
                        $dependencies = preg_replace_callback(
                            '/"([a-zA-Z0-9:]+?)"/',
                            function ($matches) use ($prefix) {
                                return '"' . $prefix . $matches[1] . '"';
                            },
                            $dependencies
                        );

                        return '"DependsOn":[' . $dependencies . ']';
                    },
                    $template
                );

                //  Update all "Fn::GetAtt": ["...", "..."] }
                $template = preg_replace_callback(
                    '/\"Fn::GetAtt"\s*:\s*\[s*"([a-zA-Z0-9:]+?)"/',
                    function ($matches) use ($prefix) {
                        return '"Fn::GetAtt": ["' . $prefix . $matches[1] . '"';
                    },
                    $template
                );
            }

            $array = json_decode($template, true);
            if ($array['AWSTemplateFormatVersion'] != '2010-09-09') {
                throw new \Exception('Invalid AWSTemplateFormatVersion');
            }
            foreach ($topLevelKeys as $topLevelKey) {
                if (isset($array[$topLevelKey])) {
                    foreach ($array[$topLevelKey] as $key => $value) {
                        $newKey = $prefix . $key;
                        if (isset($mergedTemplate[$topLevelKey][$newKey])) {
                            throw new \Exception("Duplicate key '$newKey' found in '$topLevelKey'");
                        }
                        $mergedTemplate[$topLevelKey][$newKey] = $value;
                    }
                }
            }
        }

        // If a description override is specified use it
        if (!empty($description)) {
            $mergedTemplate['Description'] = trim($description);
        }

        return json_encode($mergedTemplate, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
