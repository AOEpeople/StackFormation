<?php

namespace StackFormation;


class Deployer
{

    public function __construct(Blueprint $blueprint)
    {
        $this->blueprint = $blueprint;
    }

    protected function prepareArguments()
    {
        $bluebrintConfig = $this->blueprint->getConfig();

        //if (isset($bluebrintConfig['account'])) {
        //    $configuredAccountId = $this->resolvePlaceholders($bluebrintConfig['account'], $blueprintName, 'account');
        //    if ($configuredAccountId != $this->getConfig()->getCurrentUsersAccountId()) {
        //        throw new \Exception(sprintf("Current user's AWS account id '%s' does not match the one configured in the blueprint: '%s'",
        //            $this->getConfig()->getCurrentUsersAccountId(),
        //            $configuredAccountId
        //        ));
        //    }
        //}

        $this->blueprint->enforceProfile();

        $arguments = [
            'StackName' => $this->blueprint->getStackName(),
            'Parameters' => $this->getBlueprintParameters($blueprintName),
            'TemplateBody' => $this->getPreprocessedTemplate($blueprintName),
        ];

        if (isset($bluebrintConfig['before']) && is_array($bluebrintConfig['before']) && count($bluebrintConfig['before']) > 0) {
            $this->executeScripts($bluebrintConfig['before'], $bluebrintConfig['basepath'], $blueprintName);
        }

        if (isset($bluebrintConfig['Capabilities'])) {
            $arguments['Capabilities'] = explode(',', $bluebrintConfig['Capabilities']);
        }

        if (isset($bluebrintConfig['stackPolicy'])) {
            if (!is_file($bluebrintConfig['stackPolicy'])) {
                throw new \Exception('Stack policy "' . $bluebrintConfig['stackPolicy'] . '" not found', 1452687982);
            }
            $arguments['StackPolicyBody'] = file_get_contents($bluebrintConfig['stackPolicy']);
        }

        $arguments['Tags'] = $this->getConfig()->getBlueprintTags($blueprintName);

        return $arguments;
    }

    protected function executeScripts(array $scripts, $path, $stackName = null)
    {
        $cwd = getcwd();
        chdir($path);

        foreach ($scripts as &$script) {
            $script = $this->resolvePlaceholders($script, $stackName, 'scripts');
        }
        passthru(implode("\n", $scripts), $returnVar);
        if ($returnVar !== 0) {
            throw new \Exception('Error executing commands');
        }
        chdir($cwd);
    }


}
