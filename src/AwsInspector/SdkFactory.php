<?php

namespace AwsInspector;

/**
 * @deprecated
 */
class SdkFactory {

    /**
     * @param string $client
     * @return \Aws\AwsClientInterface
     * @throws \Exception
     * @deprecated
     */
    public static function getClient($client, $profile='default', array $args=[]) {
        static $profileManager;
        if (empty($profileManager)) {
            $profileManager = new \StackFormation\Profile\Manager();
        }
        return $profileManager->getClient($client, $profile, $args);
    }

}
