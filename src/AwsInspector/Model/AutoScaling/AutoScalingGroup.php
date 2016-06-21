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

    protected $availableProcesses = [
        'Launch',
        'Terminate',
        'HealthCheck',
        'ReplaceUnhealthy',
        'AZRebalance',
        'AlarmNotification',
        'ScheduledActions',
        'AddToLoadBalancer'
    ];

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

    public function detachLoadBalancers(array $loadBalancers) {
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
        $result = $asgClient->detachLoadBalancers([
            'AutoScalingGroupName' => $this->getAutoScalingGroupName(),
            'LoadBalancerNames' => $loadBalancerNames,
        ]);
        return $result;
    }

    public function suspendProcesses($processes) {
        if (is_string($processes) && $processes == 'all') {
            $processes = $this->availableProcesses;
        }
        if (!is_array($processes)) {
            throw new \InvalidArgumentException('Argument must be "all" or an array of processes');
        }
        foreach ($processes as $process) {
            if (!in_array($process, $this->availableProcesses)) {
                throw new \InvalidArgumentException("Process '$processes' is invalid'");
            }
        }

        $asgClient = \AwsInspector\SdkFactory::getClient('AutoScaling'); /* @var $asgClient \Aws\AutoScaling\AutoScalingClient */
        $result = $asgClient->suspendProcesses([
            'AutoScalingGroupName' => $this->getAutoScalingGroupName(),
            'ScalingProcesses' => $processes,
        ]);
        // will throw exception if it didn't work
    }

    public function resumeProcesses($processes) {
        if (is_string($processes) && $processes == 'all') {
            $processes = $this->availableProcesses;
        }
        if (!is_array($processes)) {
            throw new \InvalidArgumentException('Argument must be "all" or an array of processes');
        }
        foreach ($processes as $process) {
            if (!in_array($process, $this->availableProcesses)) {
                throw new \InvalidArgumentException("Process '$processes' is invalid'");
            }
        }

        $asgClient = \AwsInspector\SdkFactory::getClient('AutoScaling'); /* @var $asgClient \Aws\AutoScaling\AutoScalingClient */
        $result = $asgClient->resumeProcesses([
            'AutoScalingGroupName' => $this->getAutoScalingGroupName(),
            'ScalingProcesses' => $processes,
        ]);
        // will throw exception if it didn't work
    }

}
