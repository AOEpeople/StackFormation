<?php

namespace StackFormation\ValueResolver;

use Aws\CloudFormation\Exception\CloudFormationException;
use StackFormation\Helper;
use StackFormation\ValueResolver;

class ProfileSwitcher extends AbstractValueResolverStage
{

    public function invoke($string)
    {
        $string = preg_replace_callback(
            '/\[profile:([^:\]\[]+?):([^\]\[]+?)\]/',
            function ($matches) {
                try {
                    $profile = $matches[1];
                    $substring = $matches[2];

                    // recursively create another ValueResolver, but this time with a different profile
                    $subValueResolver = new ValueResolver(
                        $this->dependencyTracker,
                        $this->profileManager,
                        $this->config,
                        $profile
                    );
                    return $subValueResolver->resolvePlaceholders($substring, $this->sourceBlueprint, $this->sourceType, $this->sourceKey);
                } catch (CloudFormationException $e) {
                    $extractedMessage = Helper::extractMessage($e);
                    throw new \Exception("Error resolving '{$matches[0]}'{$this->getExceptionMessageAppendix()} (CloudFormation error: $extractedMessage)");
                }
            },
            $string
        );
        return $string;
    }

}
