<?php

namespace StackFormation;

use StackFormation\Helper\Div;

class TemplateMerger
{
    const MAX_CF_TEMPLATE_SIZE = 51200;

    /**
     * @param \StackFormation\Template[] $templates
     * @param string|null                $description
     * @param array                      $additionalData
     *
     * @return string
     * @throws \Exception
     */
    public function merge(array $templates, $description = null, array $additionalData = [])
    {
        if (count($templates) == 0) {
            throw new \InvalidArgumentException('No templates given');
        }

        $mergedTemplate = ['AWSTemplateFormatVersion' => '2010-09-09'];

        $mergeKeys = [
            'Parameters',
            'Mappings',
            'Conditions',
            'Resources',
            'Outputs',
            'Metadata',
        ];

        foreach ($templates as $template) {
            if (!$template instanceof \StackFormation\Template) {
                throw new \InvalidArgumentException('Expecting an array of \StackFormation\Template objects');
            }

            try {
                $data = $template->getData();

                // Copy the current description into the final template
                if (!empty($data['Description'])) {
                    $mergedTemplate['Description'] = $data['Description'];
                }

                // Merge keys from current template with final template
                foreach ($mergeKeys as $mergeKey) {
                    if (isset($data[$mergeKey]) && is_array($data[$mergeKey])) {
                        foreach ($data[$mergeKey] as $key => $value) {
                            if (isset($mergedTemplate[$mergeKey][$key])) {
                                // it's ok if the parameter has the same name and type...
                                if (($mergeKey != 'Parameters') || ($value['Type'] != $mergedTemplate[$mergeKey][$key]['Type'])) {
                                    throw new \Exception("Duplicate key '$key' found in '$mergeKey'");
                                }
                            }
                            $mergedTemplate[$mergeKey][$key] = $value;
                        }
                    }
                }
            } catch (TemplateDecodeException $e) {
                if (Div::isProgramInstalled('jq')) {
                    $tmpfile = tempnam(sys_get_temp_dir(), 'json_validate_');
                    $yaml = new \Symfony\Component\Yaml\Yaml();
                    file_put_contents($tmpfile, $yaml->dump($data));
                    passthru('jq . ' . $tmpfile);
                    unlink($tmpfile);
                }

                throw $e;
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

        $yaml = new \Symfony\Component\Yaml\Yaml();
        $output = $yaml->dump($mergedTemplate);

        // Check for max template size
        if (strlen($output) > self::MAX_CF_TEMPLATE_SIZE) {
            $output = $yaml->dump($mergedTemplate, 1);

            // Re-check for max template size
            if (strlen($output) > self::MAX_CF_TEMPLATE_SIZE) {
                throw new \Exception(sprintf('Template too big (%s bytes). Maximum template size is %s bytes.', strlen($output), self::MAX_CF_TEMPLATE_SIZE));
            }
        }

        return $output;
    }
}
