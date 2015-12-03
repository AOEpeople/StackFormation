<?php

namespace StackFormation;

use Aws\Sdk;

class SdkFactory
{
    private static $sdk;
    private static $clients = [];

    public static function getSdk()
    {
        if (is_null(self::$sdk)) {
            $region = getenv('AWS_DEFAULT_REGION');
            if (empty($region)) {
                throw new \Exception('No valid region found in AWS_DEFAULT_REGION env var.');
            }

            if (!getenv('USE_INSTANCE_PROFILE')) {
                if (!getenv('AWS_ACCESS_KEY_ID')) {
                    throw new \Exception('No valid access key found in AWS_ACCESS_KEY_ID env var.');
                }
                if (!getenv('AWS_SECRET_ACCESS_KEY')) {
                    throw new \Exception('No valid secret access key found in AWS_SECRET_ACCESS_KEY env var.');
                }
            }

            self::$sdk = new Sdk(
                [
                    'region'  => $region,
                    'version' => 'latest',
                ]
            );
        }

        return self::$sdk;
    }

    /**
     * @param string $client
     * @param array  $args
     *
     * @return \Aws\AwsClientInterface
     * @throws \Exception
     */
    public static function getClient($client, array $args = [])
    {
        $key = $client . serialize($args);
        if (!isset(self::$clients[$key])) {
            self::$clients[$key] = self::getSdk()->createClient($client, $args);
        }

        return self::$clients[$key];
    }

    /**
     * @return \Aws\CloudFormation\CloudFormationClient
     */
    public static function getCfnClient()
    {
        return self::getClient('CloudFormation');
    }
}
