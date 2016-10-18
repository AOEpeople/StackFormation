<?php

namespace StackFormation\ValueResolver\Stage;

class ProfileSwitcher extends AbstractValueResolverStage
{

    public function invoke($string)
    {
        $string = preg_replace_callback(
            '/\[profile:([^:\]\[]+?):([^\]\[]+?)\]/',
            function ($matches) {

                // [profile:...] ignores AWS_UNSET_PROFILE. Backup up value here
                $unsetProfileBackup = getenv('AWS_UNSET_PROFILE');
                putenv('AWS_UNSET_PROFILE');

                // recursively create another ValueResolver, but this time with a different profile
                $subValueResolver = new \StackFormation\ValueResolver\ValueResolver(
                    $this->valueResolver->getDependencyTracker(),
                    $this->valueResolver->getProfileManager(),
                    $this->valueResolver->getConfig(),
                    $matches[1]
                );
                $value = $subValueResolver->resolvePlaceholders($matches[2], $this->sourceBlueprint, $this->sourceType, $this->sourceKey);

                // restoring AWS_UNSET_PROFILE value if it was set before
                if ($unsetProfileBackup) {
                    putenv('AWS_UNSET_PROFILE=1');
                }
                return $value;
            },
            $string
        );
        return $string;
    }

}
