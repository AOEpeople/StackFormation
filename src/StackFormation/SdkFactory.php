<?php

namespace StackFormation;

use Aws\Sdk;

class SdkFactory
{
    private static $sdk = [];
    private static $clients = [];

    public static function getSdk()
    {
        $region = getenv('AWS_DEFAULT_REGION');
        if (empty($region)) {
            throw new \Exception('No valid region found in AWS_DEFAULT_REGION env var.');
        }
        $useInstanceProfile = getenv('USE_INSTANCE_PROFILE');
        $accessKey = getenv('AWS_ACCESS_KEY_ID');
        $secretKey = getenv('AWS_SECRET_ACCESS_KEY');

        if (!$useInstanceProfile) {
            if (!$accessKey) {
                throw new \Exception('No valid access key found in AWS_ACCESS_KEY_ID env var (or set USE_INSTANCE_PROFILE).');
            }
            if (!$secretKey) {
                throw new \Exception('No valid secret access key found in AWS_SECRET_ACCESS_KEY env var (or set USE_INSTANCE_PROFILE).');
            }
        }

        $assumeRole = getenv('AWS_ASSUME_ROLE');

        $conf = implode('|', [$region,$useInstanceProfile,$accessKey,$secretKey,$assumeRole]);

        if (empty(self::$sdk[$conf])) {
            $sdk = new Sdk([
                'region'  => $region,
                'version' => 'latest'
            ]);

            if ($assumeRole) {
                $stsClient = $sdk->createSts();
                $res = $stsClient->assumeRole([
                    'RoleArn' => getenv('AWS_ASSUME_ROLE'),
                    'RoleSessionName' => 'StackFormation'
                ]);
                $credentials = $stsClient->createCredentials($res);

                // replace SDK object with new one that is assuming the role
                $sdk = new Sdk([
                    'region'  => $region,
                    'version' => 'latest',
                    'credentials' => $credentials
                ]);
            }

            self::$sdk[$conf] = $sdk;
        }

        return self::$sdk[$conf] ;
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
