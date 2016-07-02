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
 */
class AutoScalingGroup extends \AwsInspector\Model\AbstractResource
{
    /**
     * @var array
     */
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

    /**
     * @param array $loadBalancers
     * @return \Aws\Result
     */
    public function attachLoadBalancers(array $loadBalancers) {
        /* @var $asgClient \Aws\AutoScaling\AutoScalingClient */
        $asgClient = $this->profileManager->getClient('AutoScaling');
        $loadBalancerNames = $this->validateElbParam($loadBalancers);

        $result = $asgClient->attachLoadBalancers([
            'AutoScalingGroupName' => $this->getAutoScalingGroupName(),
            'LoadBalancerNames' => $loadBalancerNames,
        ]);
        
        return $result;
    }

    /**
     * @param array $loadBalancers
     * @return \Aws\Result
     */
    public function detachLoadBalancers(array $loadBalancers) {
        /* @var $asgClient \Aws\AutoScaling\AutoScalingClient */
        $asgClient = $this->profileManager->getClient('AutoScaling');
        $loadBalancerNames = $this->validateElbParam($loadBalancers);

        $result = $asgClient->detachLoadBalancers([
            'AutoScalingGroupName' => $this->getAutoScalingGroupName(),
            'LoadBalancerNames' => $loadBalancerNames,
        ]);

        return $result;
    }

    /**
     * @param string $processes
     */
    public function suspendProcesses($processes = 'all') {
        /* @var $asgClient \Aws\AutoScaling\AutoScalingClient */
        $asgClient = $this->profileManager->getClient('AutoScaling');
        $processes = $this->validateProcessesParam($processes);

        $asgClient->suspendProcesses([
            'AutoScalingGroupName' => $this->getAutoScalingGroupName(),
            'ScalingProcesses' => $processes,
        ]);

        // will throw exception if it didn't work
    }

    /**
     * @param string $processes
     */
    public function resumeProcesses($processes = 'all') {
        /* @var $asgClient \Aws\AutoScaling\AutoScalingClient */
        $asgClient = $this->profileManager->getClient('AutoScaling');
        $processes = $this->validateProcessesParam($processes);

        $asgClient->resumeProcesses([
            'AutoScalingGroupName' => $this->getAutoScalingGroupName(),
            'ScalingProcesses' => $processes,
        ]);

        // will throw exception if it didn't work
    }

    /**
     * @param $processes
     * @return array|string
     */
    protected function validateProcessesParam($processes) {
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

        return $processes;
    }

    /**
     * @param array $loadBalancers
     * @return array
     */
    protected function validateElbParam(array $loadBalancers) {
        $loadBalancerNames = [];
        foreach ($loadBalancers as $loadBalancer) {
            if (is_string($loadBalancer)) {
                $loadBalancerNames[] = $loadBalancer;
                continue;
            }

            if (is_object($loadBalancer) && $loadBalancer instanceof \AwsInspector\Model\Elb\Elb) {
                $loadBalancerNames[] = $loadBalancer->getLoadBalancerName();
                continue;
            }

            throw new \InvalidArgumentException('Argument must be an array of strings or \AwsInspector\Model\Elb\Elb objects');
        }

        return $loadBalancerNames;
    }
}
