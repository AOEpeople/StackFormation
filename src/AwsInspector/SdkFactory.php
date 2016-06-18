<?php

namespace AwsInspector;

class SdkFactory {

    protected static $sdks=[];

    /**
     * @return \Aws\Sdk
     */
    public static function getSdk($profile='default')
    {
        if (!isset(self::$sdks[$profile])) {
            $params = [
                'version' => 'latest',
                'region' => getenv('AWS_DEFAULT_REGION'),
                'retries' => 20
            ];
            if ($profile != 'default') {
                $profileManager = new ProfileManager();
                $profileConfig = $profileManager->getProfileConfig($profile);
                $params['region'] = $profileConfig['region'];
                $params['credentials'] = [
                    'key' => $profileConfig['access_key'],
                    'secret' => $profileConfig['secret_key']
                ];
            }
            self::$sdks[$profile] = new \Aws\Sdk($params);
        }
        return self::$sdks[$profile];
    }

    /**
     * @param string $client
     * @return \Aws\AwsClientInterface
     * @throws \Exception
     */
    public static function getClient($client, $profile='default', array $args=[]) {
        return self::getSdk($profile)->createClient($client, $args);
    }

}
