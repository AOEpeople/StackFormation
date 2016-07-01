<?php

namespace StackFormation;

class TemplateMerger
{

    public function merge(array $templates, $description = null, array $additionalData = [])
    {
        if (count($templates) == 0) {
            throw new \InvalidArgumentException('No templates given');
        }

        $mergedTemplate = [
            'AWSTemplateFormatVersion' => '2010-09-09'
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
                $template = $this->updateRef($prefix, $template);
                $template = $this->updateDependsOn($prefix, $template);
                $template = $this->updateDependsOnMultiple($prefix, $template);
                $template = $this->updateFnGetAtt($prefix, $template);
            }

            $array = json_decode($template, true);
            if (!is_array($array)) {
                throw new \Exception('Error decoding file ' . $key);
            }
            if ($array['AWSTemplateFormatVersion'] != '2010-09-09') {
                throw new \Exception('Invalid AWSTemplateFormatVersion');
            }
            if (!empty($array['Description'])) {
                $mergedTemplate['Description'] = $array['Description'];
            }
            foreach ($topLevelKeys as $topLevelKey) {
                if (isset($array[$topLevelKey])) {
                    foreach ($array[$topLevelKey] as $key => $value) {
                        $newKey = $prefix . $key;
                        if (isset($mergedTemplate[$topLevelKey][$newKey])) {
                            // it's ok if the parameter has the same name and type...
                            if (($topLevelKey != 'Parameters') || ($value['Type'] != $mergedTemplate[$topLevelKey][$newKey]['Type'])) {
                                throw new \Exception("Duplicate key '$newKey' found in '$topLevelKey'");
                            }
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
        if (empty($mergedTemplate['Description'])) {
            $mergedTemplate['Description'] = 'Merged Template';
        }

        $mergedTemplate = array_merge_recursive($mergedTemplate, $additionalData);

        $json = json_encode($mergedTemplate, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (strlen($json) > 51200) { // that's the maximum allowed size of a CloudFormation template
            $json = json_encode($mergedTemplate, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        if (strlen($json) > 51200) {
            throw new \Exception('Template too big. (Must be smaller than 51200 bytes)');
        }
        return $json;
    }

    /**
     * @param $prefix
     * @param $template
     * @return mixed
     */
    public function updateRef($prefix, $template)
    {
        // Update all { "Ref": "..." }
        $template = preg_replace_callback(
            '/\{\s*"Ref"\s*:\s*"([a-zA-Z0-9:]+?)"\s*\}/',
            function ($matches) use ($prefix) {
                return '{"Ref":"' . $prefix . $matches[1] . '"}';
            },
            $template
        );
        return $template;
    }

    /**
     * @param $prefix
     * @param $template
     * @return mixed
     */
    public function updateDependsOn($prefix, $template)
    {
        // Update all { "DependsOn": "..." }
        $template = preg_replace_callback(
            '/\"DependsOn"\s*:\s*"([a-zA-Z0-9:]+?)"/',
            function ($matches) use ($prefix) {
                return '"DependsOn":"' . $prefix . $matches[1] . '"';
            },
            $template
        );
        return $template;
    }

    /**
     * @param $prefix
     * @param $template
     * @return mixed
     */
    public function updateDependsOnMultiple($prefix, $template)
    {
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
        return $template;
    }

    /**
     * @param $prefix
     * @param $template
     * @return mixed
     */
    public function updateFnGetAtt($prefix, $template)
    {
        //  Update all "Fn::GetAtt": ["...", "..."] }
        $template = preg_replace_callback(
            '/\"Fn::GetAtt"\s*:\s*\[s*"([a-zA-Z0-9:]+?)"/',
            function ($matches) use ($prefix) {
                return '"Fn::GetAtt": ["' . $prefix . $matches[1] . '"';
            },
            $template
        );
        return $template;
    }
}
