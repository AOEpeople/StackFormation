<?php

namespace StackFormation\ValueResolver;

use StackFormation\Helper\Pipeline;
use StackFormation\Profile\Manager;
use StackFormation\DependencyTracker;
use StackFormation\Config;
use StackFormation\Blueprint;

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
    public function __construct(DependencyTracker $dependencyTracker=null, Manager $profileManager=null, Config $config=null, $forceProfile=null)
    {
        $this->dependencyTracker = $dependencyTracker ?: new DependencyTracker();
        $this->profileManager = $profileManager ?: new Manager();
        $this->config = $config ?: new Config();
        $this->forceProfile = $forceProfile;
    }

    /**
     * Resolve placeholders
     *
     * @param $string
     * @param Blueprint|null $sourceBlueprint
     * @param string $sourceType
     * @param string $sourceKey
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
            '\StackFormation\ValueResolver\Stage\ProfileSwitcher',
            '\StackFormation\ValueResolver\Stage\EnvironmentVariable',
            '\StackFormation\ValueResolver\Stage\EnvironmentVariableWithFallback',
            '\StackFormation\ValueResolver\Stage\Variable',
            '\StackFormation\ValueResolver\Stage\ConditionalValue',
            '\StackFormation\ValueResolver\Stage\Tstamp',
            '\StackFormation\ValueResolver\Stage\Md5',
            '\StackFormation\ValueResolver\Stage\StackOutput',
            '\StackFormation\ValueResolver\Stage\StackResource',
            '\StackFormation\ValueResolver\Stage\StackParameter',
            '\StackFormation\ValueResolver\Stage\Clean',
        ];

        $pipeline = new Pipeline();
        foreach ($stageClasses as $stageClass) {
            $pipeline->addStage(new $stageClass($this, $sourceBlueprint, $sourceType, $sourceKey));
        }

        $originalString = $string;
        $string = $pipeline->process($string);

        return ($string == $originalString) ? $string : $this->resolvePlaceholders($string, $sourceBlueprint, $sourceType, $sourceKey, $circuitBreaker+1);
    }

    /**
     * @return DependencyTracker
     */
    public function getDependencyTracker()
    {
        return $this->dependencyTracker;
    }

    /**
     * @return Manager
     */
    public function getProfileManager()
    {
        return $this->profileManager;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param Blueprint|null $sourceBlueprint
     * @return \StackFormation\StackFactory
     */
    public function getStackFactory(Blueprint $sourceBlueprint=null)
    {
        if (!is_null($this->forceProfile)) {
            return $this->profileManager->getStackFactory($this->forceProfile);
        }
        return $this->profileManager->getStackFactory($sourceBlueprint ? $sourceBlueprint->getProfile() : null);
    }

}