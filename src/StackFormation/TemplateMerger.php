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
                $array = $template->getDecodedJson();

                // Copy the current description into the final template
                if (!empty($array['Description'])) {
                    $mergedTemplate['Description'] = $array['Description'];
                }

                // Merge keys from current template with final template
                foreach ($mergeKeys as $mergeKey) {
                    if (isset($array[$mergeKey]) && is_array($array[$mergeKey])) {
                        foreach ($array[$mergeKey] as $key => $value) {
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
                    file_put_contents($tmpfile, $template->getProcessedTemplate());
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

        $json = json_encode($mergedTemplate, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Check for max template size
        if (strlen($json) > self::MAX_CF_TEMPLATE_SIZE) {
            $json = json_encode($mergedTemplate, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // Re-check for max template size
            if (strlen($json) > self::MAX_CF_TEMPLATE_SIZE) {
                throw new \Exception(sprintf('Template too big (%s bytes). Maximum template size is %s bytes.', strlen($json), self::MAX_CF_TEMPLATE_SIZE));
            }
        }

        return $json;
    }
}
