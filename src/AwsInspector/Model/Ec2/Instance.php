<?php

namespace AwsInspector\Model\Ec2;

use AwsInspector\Helper\Curl;
use AwsInspector\Ssh\Connection;
use AwsInspector\Ssh\PrivateKey;

/**
 * Class Instance
 *
 * @method getInstanceId()
 * @method getTags()
 * @method getPublicIpAddress()
 * @method getPrivateIpAddress()
 * @method getImageId()
 * @method getState()
 * @method getPrivateDnsName()
 * @method getPublicDnsName()
 * @method getStateTransitionReason()
 * @method getKeyName()
 * @method getAmiLaunchIndex()
 * @method getProductCodes()
 * @method getInstanceType()
 * @method getLaunchTime()
 * @method getPlacement()
 * @method getMonitoring()
 * @method getSubnetId()
 * @method getVpcId()
 * @method getArchitecture()
 * @method getRootDeviceType()
 * @method getRootDeviceName()
 * @method getBlockDeviceMappings()
 * @method getVirtualizationType()
 * @method getClientToken()
 * @method getSecurityGroups()
 * @method getSourceDestCheck()
 * @method getHypervisor()
 * @method getNetworkInterfaces()
 * @method getEbsOptimized()
 */
class Instance extends \AwsInspector\Model\AbstractResource
{

    protected $username;

    protected $multiplexSshConnection = false;

    public function getDefaultUsername()
    {
        if (is_null($this->username)) {
            if ($user = $this->getInspectorConfiguration('user')) {
                $this->username = $user;
            } elseif ($user = $this->getTag('inspector:user')) {
                // deprecated!
                $this->username = $user;
            } elseif ($user = getenv('AWSINSPECTOR_DEFAULT_EC2_USER')) {
                $this->username = $user;
            } else {
                $this->username = 'ec2-user';
                $ami = $this->getImageId();
                if (in_array($ami, ['ami-47a23a30', 'ami-47360a30', 'ami-d05e75b8'])) {
                    $this->username = 'ubuntu';
                }
            }
        }
        return $this->username;
    }

    public function getPrivateKey()
    {
        $keyName = $this->getKeyName();
        if (empty($keyName)) {
            return null;
            // throw new \Exception('No KeyName found');
        }
        return PrivateKey::get('keys/' . $keyName . '.pem');
    }

    /**
     * Get jump host (bastion server)
     *
     * Overwrite this method in your inheriting class and return
     * a \AwsInspector\Model\Ec2\Instance representing your bastion server
     *
     * @return Instance|null
     * @throws \Exception
     */
    public function getJumpHost()
    {
        if ($config = $this->getInspectorConfiguration('jumptags')) {
            $ec2Repository = new Repository();
            $instances = $ec2Repository->findEc2InstancesByTags($config);
            if (count($instances) == 0) {
                throw new \Exception('Could not find jump host for: ' . var_export($config, true));
            }
            return $instances->getFirst();
        }
        return null;
    }

    protected function getInspectorConfiguration($type)
    {
        $configString = $this->getTag('inspector') ? $this->getTag('inspector') : $this->getTag('inspector:jump');
        if (!$configString) {
            return false;
        }
        $tagPairs = explode(',', $configString);
        $config = [];
        foreach ($tagPairs as $tagPair) {
            list($key, $value) = explode(':', $tagPair);
            $config[trim($key)] = trim($value);
        }
        if ($type == 'user') {
            return isset($config['User']) ?  $config['User'] : false;
        }
        if ($type == 'jumptags') {
            if (isset($config['User'])) {
                unset($config['User']);
            }
            return count($config) ? $config : false;
        }
        throw new \InvalidArgumentException("Invalid type: $type");
    }

    public function getConnectionIp()
    {
        return $this->getPublicIpAddress() ? $this->getPublicIpAddress() : $this->getPrivateIpAddress();
    }

    /**
     * Get SSH connection
     *
     * @return Connection
     * @throws \Exception
     */
    public function getSshConnection($multiplex=null)
    {
        $jumpHost = $this->getJumpHost();
        return new Connection(
            $this->getDefaultUsername(),
            $jumpHost ? $this->getPrivateIpAddress() : $this->getConnectionIp(),
            $this->getPrivateKey(),
            $jumpHost,
            !is_null($multiplex) ? $multiplex : $this->multiplexSshConnection
        );
    }

    /**
     * @return \AwsInspector\Model\Collection
     */
    public function getEbsVolumes()
    {
        $ebsRepository = new \AwsInspector\Model\Ebs\Repository();
        return $ebsRepository->findEbsVolumesByInstanceId($this->getInstanceId());
    }

    public function exec($command, $asUser=null)
    {
        return $this->getSshConnection()->exec($command, $asUser);
    }

    public function fileExists($file, $asUser=null)
    {
        $result = $this->exec('test -f ' . escapeshellarg($file), $asUser);
        return ($result['returnVar'] == 0);
    }

    public function directoryExists($file, $asUser=null)
    {
        $result = $this->exec('test -d ' . escapeshellarg($file), $asUser);
        return ($result['returnVar'] == 0);
    }

    public function linkExists($file, $asUser=null)
    {
        $result = $this->exec('test -l ' . escapeshellarg($file), $asUser);
        return ($result['returnVar'] == 0);
    }

    public function getFileContent($file, $asUser=null)
    {
        $result = $this->exec('cat ' . escapeshellarg($file), $asUser);
        return implode("\n", $result['output']);
    }

    public function getHttpStatusCode($url)
    {
        $curlHelper = new Curl($url, [], $this->getSshConnection());
        $curlHelper->doRequest();
        return $curlHelper->getResponseCode();
    }

    public function canConnectTo($hostname, $port, $timeout=1)
    {
        $result = $this->exec(sprintf('nc -z -w%s %s %s',
            escapeshellarg($timeout),
            escapeshellarg($hostname),
            escapeshellarg($port)
        ));
        if ($result['returnVar'] != 0) {
            throw new \Exception("Can't connect to $hostname:$port");
        }
        return true;
    }

    public function terminate()
    {
        $ec2Client = \AwsInspector\SdkFactory::getClient('ec2');/* @var $ec2Client \Aws\Ec2\Ec2Client */
        $ec2Client->terminateInstances([
            'InstanceIds' => [ $this->getInstanceId() ]
        ]);
    }

}
