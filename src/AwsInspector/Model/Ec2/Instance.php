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
            if ($this->getTag('inspector:user')) {
                $this->username = $this->getTag('inspector:user');
            } elseif (getenv('AWSINSPECTOR_DEFAULT_EC2_USER')) {
                $this->username = getenv('AWSINSPECTOR_DEFAULT_EC2_USER');
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
            throw new \Exception('No KeyName found');
        }
        return PrivateKey::get('keys/' . $keyName . '.pem');
    }

    /**
     * Get jump host (bastion server)
     *
     * Overwrite this method in your inheriting class and return
     * a \AwsInspector\Model\Ec2\Instance representing your bastion server
     *
     * @return null|Instance
     */
    public function getJumpHost()
    {
        if ($jumpHostTags = $this->getTag('inspector:jump')) {
            $ec2Repository = new Repository();
            $tagPairs = explode(',', $jumpHostTags);
            $tags = [];
            foreach ($tagPairs as $tagPair) {
                list($key, $value) = explode(':', $tagPair);
                $tags[trim($key)] = trim($value);
            }
            return $ec2Repository->findEc2InstancesByTags($tags)->getFirst();
        }
        return null;
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
    public function getSshConnection()
    {
        $jumpHost = $this->getJumpHost();
        return new Connection(
            $this->getDefaultUsername(),
            $jumpHost ? $this->getPrivateIpAddress() : $this->getConnectionIp(),
            $this->getPrivateKey(),
            $jumpHost,
            $this->multiplexSshConnection
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
        if ($asUser) {
            $command = 'bash -c ' . escapeshellarg($command);
            $command = 'sudo -u '.escapeshellarg($asUser) . ' '. escapeshellarg($command);
        }
        return $this->getSshConnection()->exec($command);
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
        $curlHelper = new Curl($url);
        $result = $this->exec($curlHelper->getResponseStatusCodeCommand());
        return intval(end($result['output']));
    }

}
