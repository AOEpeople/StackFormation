<?php

namespace AwsInspector\Model\Elb;

/**
 * Class Elb
 *
 * @method getLoadBalancerName()
 * @method getDNSName()
 * @method getCanonicalHostedZoneNameID()
 * @method getListenerDescriptions()
 * @method getPolicies()
 * @method getBackendServerDescriptions()
 * @method getAvailabilityZones()
 * @method getSubnets()
 * @method getVPCId()
 * @method getInstances()
 * @method getHealthCheck()
 * @method getSourceSecurityGroup()
 * @method getSecurityGroups()
 * @method getCreatedTime()
 * @method getScheme()
 */
class Elb extends \AwsInspector\Model\AbstractResource
{
    protected $tags;

    public function getTags()
    {
        if (is_null($this->tags)) {
            $elbClient = \AwsInspector\SdkFactory::getClient('ElasticLoadBalancing');
            /* @var $elbClient \Aws\ElasticLoadBalancing\ElasticLoadBalancingClient */
            $result = $elbClient->describeTags(['LoadBalancerNames' => [$this->getLoadBalancerName()]]);
            $this->tags = $result->search('TagDescriptions[0].Tags');
        }
        return $this->tags;
    }

    public function getInstanceStates()
    {
        $elbClient = \AwsInspector\SdkFactory::getClient('ElasticLoadBalancing');
        /* @var $elbClient \Aws\ElasticLoadBalancing\ElasticLoadBalancingClient */
        $res = $elbClient->describeInstanceHealth(['LoadBalancerName' => $this->getLoadBalancerName()]);
        $instances = [];
        foreach ($res->search('InstanceStates[]') as $instanceState) {
            $instances[$instanceState['InstanceId']] = $instanceState;
        }
        return $instances;
    }

}
