<?php

namespace StackFormation\Profile;

use StackFormation\StackFactory;
use Symfony\Component\Console\Output\OutputInterface;

class Manager {

    protected $sdk;
    protected $clients = [];
    protected $stackFactories = [];
    protected $credentialProvider;
    protected $output;

    public function __construct(YamlCredentialProvider $credentialProvider=null, OutputInterface $output=null)
    {
        $this->credentialProvider = is_null($credentialProvider) ? new YamlCredentialProvider() : $credentialProvider;
        $this->output = $output;
    }

    protected function getSdk()
    {
        if (is_null($this->sdk)) {
            $region = getenv('AWS_DEFAULT_REGION');
            if (empty($region)) {
                throw new \Exception('Environment variable AWS_DEFAULT_REGION not set.');
            }

            $parameters = [
                'version' => 'latest',
                'region' => $region,
                'retries' => 5,
                'http' => [ 'connect_timeout' => 20 ]
            ];

            $parameters['http_handler'] = $this->getHttpHandler();

            $this->sdk = new \Aws\Sdk($parameters);
        }
        return $this->sdk;
    }

    /**
     * @return \Aws\Handler\GuzzleV6\GuzzleHandler
     */
    private function getHttpHandler()
    {
        $guzzleStack = \GuzzleHttp\HandlerStack::create();

        $guzzleStack->push(\GuzzleHttp\Middleware::retry(
            function (
                $retries,
                \GuzzleHttp\Psr7\Request $request,
                \GuzzleHttp\Psr7\Response $response = null,
                \GuzzleHttp\Exception\RequestException $exception = null
            )
            {
                if ($retries >= 5) {
                    return false;
                }

                if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                    return true;
                }

                if ($response) {
                    if ($response->getStatusCode() == 400) {
                        return true;
                    }
                }
                return false;
            }
        ));

        if ($this->output && $this->output->isVeryVerbose()) {
            #$guzzleStack = \GuzzleHttp\HandlerStack::create();
            $guzzleStack->push(\GuzzleHttp\Middleware::log(
                new \Monolog\Logger('main'),
                // new \GuzzleHttp\MessageFormatter('{req_body}')
                new \GuzzleHttp\MessageFormatter('[{code}] {req_body}')
            ));

        }

        return new \Aws\Handler\GuzzleV6\GuzzleHandler(new \GuzzleHttp\Client(['handler' => $guzzleStack]));
    }

    /**
     * @param string $client
     * @param string $profile
     * @param array $args
     * @return \Aws\AwsClientInterface
     * @throws \Exception
     */
    public function getClient($client, $profile=null, array $args=[]) {
        if (!is_string($client)) {
            throw new \InvalidArgumentException('Client parameter must be a string');
        }
        if (!is_null($profile) && !is_string($profile)) {
            throw new \InvalidArgumentException('Profile parameter must be a string');
        }
        $cacheKey = md5(json_encode([$client, $profile, $args]));
        if (!isset($this->clients[$cacheKey])) {
            if ($profile && !isset($args['credentials'])) {
                $args['credentials'] = $this->credentialProvider->getCredentialsForProfile($profile);
            }
            $this->printDebug($client, $profile);
            $this->clients[$cacheKey] = $this->getSdk()->createClient($client, $args);
        }
        return $this->clients[$cacheKey];
    }

    protected function printDebug($client, $profile) {
        if (!$this->output || !$this->output->isVerbose()) {
            return;
        }
        $message = "[ProfileManager] Created '$client' client";
        if ($profile) {
            $message .= " for profile '$profile'";
        } elseif ($profileFromEnv = getenv('AWSINSPECTOR_PROFILE')) {
            $message .= " for profile '$profileFromEnv' with default credentials provider (env/ini/instance)";
        } else {
            $message .= " with default credentials provider (env/ini/instance)";
        }
        $this->output->writeln($message);
    }

    /**
     * @return \Aws\CloudFormation\CloudFormationClient
     */
    public function getCfnClient($profile=null, array $args=[]) {
        return $this->getClient('CloudFormation', $profile, $args);
    }

    public function listAllProfiles()
    {
        return $this->credentialProvider->listAllProfiles();
    }

    public function getEnvVarsFromProfile($profile) {
        $tmp = [];
        foreach ($this->credentialProvider->getEnvVarsForProfile($profile) as $var => $value) {
            $tmp[] = "$var=$value";
        }
        return $tmp;
    }

    public function writeProfileToDotEnv($profile, $file='.env') {
        $tmp = $this->getEnvVarsFromProfile($profile);
        $res = file_put_contents($file, implode("\n", $tmp));
        if ($res === false) {
            throw new \Exception('Error while writing file .env');
        }
        return $file;
    }

    /**
     * "StackFactory" Factory :)
     *
     * @param $profile
     * @return StackFactory
     */
    public function getStackFactory($profile=null) {
        $cachKey = ($profile ? $profile : '__empty__');
        if (!isset($this->stackFactories[$cachKey])) {
            $this->stackFactories[$cachKey] = new StackFactory($this->getCfnClient($profile));
        }
        return $this->stackFactories[$cachKey];
    }

}
