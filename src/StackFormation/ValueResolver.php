<?php

namespace StackFormation;

use Aws\CloudFormation\Exception\CloudFormationException;
use League\Pipeline\Pipeline;
use League\Pipeline\PipelineBuilder;
use StackFormation\Exception\MissingEnvVarException;
use StackFormation\Exception\StackNotFoundException;
use StackFormation\Profile\Manager;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class ValueResolver {

    protected $dependencyTracker;
    protected $profileManager;
    protected $config;
    protected $forceProfile;

    /**
     * PlaceholderResolver constructor.
     *
     * @param DependencyTracker $dependencyTracker
     * @param Manager $profileManager
     * @param Config $config
     * @param string $forceProfile
     */
    public function __construct(DependencyTracker $dependencyTracker=null, Manager $profileManager=null, Config $config, $forceProfile=null)
    {
        $this->dependencyTracker = $dependencyTracker ?: new DependencyTracker();
        $this->profileManager = $profileManager ?: new Manager();
        $this->forceProfile = $forceProfile;
        $this->config = $config;
    }

    /**
     * Resolve placeholders
     *
     * @param $string
     * @param Blueprint|null $sourceBlueprint
     * @param null $sourceType
     * @param null $sourceKey
     * @param int $circuitBreaker
     * @return mixed
     * @throws \Exception
     */
    public function resolvePlaceholders($string, Blueprint $sourceBlueprint=null, $sourceType=null, $sourceKey=null, $circuitBreaker=0)
    {
        if ($circuitBreaker > 20) {
            throw new \Exception('Max nesting level reached. Looks like a circular dependency.');
        }

        $stageClasses = [
            '\StackFormation\ValueResolver\ProfileSwitcher',
            '\StackFormation\ValueResolver\EnvironmentVariable',
            '\StackFormation\ValueResolver\EnvironmentVariableWithFallback',
            '\StackFormation\ValueResolver\Variable',
            '\StackFormation\ValueResolver\ConditionalValue',
            '\StackFormation\ValueResolver\Tstamp',
            '\StackFormation\ValueResolver\Md5',
            '\StackFormation\ValueResolver\StackOutput',
            '\StackFormation\ValueResolver\StackResource',
            '\StackFormation\ValueResolver\StackParameter',
            '\StackFormation\ValueResolver\Clean',
        ];

        $pipelineBuilder = new PipelineBuilder();
        foreach ($stageClasses as $stageClass) {
            $pipelineBuilder->add(new $stageClass(
                $this,
                $this->profileManager,
                $this->config,
                $this->dependencyTracker,
                $sourceBlueprint,
                $sourceType,
                $sourceKey
            ));
        }

        $originalString = $string;
        $string = $pipelineBuilder->build()->process($string);

        return ($string == $originalString)
            ? $string :
            $this->resolvePlaceholders($string, $sourceBlueprint, $sourceType, $sourceKey, $circuitBreaker+1);
    }

    public function getDependencyTracker()
    {
        return $this->dependencyTracker;
    }

    public function getStackFactory(Blueprint $sourceBlueprint=null)
    {
        if (!is_null($this->forceProfile)) {
            return $this->profileManager->getStackFactory($this->forceProfile);
        }
        return $this->profileManager->getStackFactory($sourceBlueprint ? $sourceBlueprint->getProfile() : null);
    }

}