<?php

namespace AwsInspector\Model\AutoScaling;

/**
 * Class AutoScalingGroup
 *
 * @method getAutoScalingGroupName()
 * @method getAutoScalingGroupARN()
 * @method getLaunchConfigurationName()
 * @method getMinSize()
 * @method getMaxSize()
 * @method getDesiredCapacity()
 * @method getDefaultCooldown()
 * @method getAvailabilityZones()
 * @method getLoadBalancerNames()
 * @method getHealthCheckType()
 * @method getHealthCheckGracePeriod()
 * @method getInstances()
 * @method getCreatedTime()
 * @method getSuspendedProcesses()
 * @method getVPCZoneIdentifier()
 * @method getEnabledMetrics()
 * @method getTags()
 * @method getTerminationPolicies()
 * @method getNewInstancesProtectedFromScaleIn()

 *
 */
class AutoScalingGroup extends \AwsInspector\Model\AbstractResource
{
    //protected $tags;
    //
    //public function getTags()
    //{
    //    if (is_null($this->tags)) {
    //        $elbClient = \AwsInspector\SdkFactory::getClient('ElasticLoadBalancing');
    //        /* @var $elbClient \Aws\ElasticLoadBalancing\ElasticLoadBalancingClient */
    //        $result = $elbClient->describeTags(['LoadBalancerNames' => [$this->getLoadBalancerName()]]);
    //        $this->tags = $result->search('TagDescriptions[0].Tags');
    //    }
    //    return $this->tags;
    //}
    //
    //public function getInstanceStates()
    //{
    //    $elbClient = \AwsInspector\SdkFactory::getClient('ElasticLoadBalancing');
    //    /* @var $elbClient \Aws\ElasticLoadBalancing\ElasticLoadBalancingClient */
    //    $res = $elbClient->describeInstanceHealth(['LoadBalancerName' => $this->getLoadBalancerName()]);
    //    $instances = [];
    //    foreach ($res->search('InstanceStates[]') as $instanceState) {
    //        $instances[$instanceState['InstanceId']] = $instanceState;
    //    }
    //    return $instances;
    //}

    public function attachLoadBalancers(array $loadBalancers) {
        $loadBalancerNames = [];
        foreach ($loadBalancers as $loadBalancer) {
            if (is_string($loadBalancer)) {
                $loadBalancerNames[] = $loadBalancer;
            } elseif (is_object($loadBalancer) && $loadBalancer instanceof \AwsInspector\Model\Elb\Elb) {
                $loadBalancerNames[] = $loadBalancer->getLoadBalancerName();
            } else {
                throw new \InvalidArgumentException('Argument must be an array of strings or \AwsInspector\Model\Elb\Elb objects');
            }
        }
        $asgClient = \AwsInspector\SdkFactory::getClient('AutoScaling'); /* @var $asgClient \Aws\AutoScaling\AutoScalingClient */
        $result = $asgClient->attachLoadBalancers([
            'AutoScalingGroupName' => $this->getAutoScalingGroupName(),
            'LoadBalancerNames' => $loadBalancerNames,
        ]);
        return $result;
    }

}
