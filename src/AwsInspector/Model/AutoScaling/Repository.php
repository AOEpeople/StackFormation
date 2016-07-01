<?php

namespace AwsInspector\Model\AutoScaling;

class Repository
{

    /**
     * @var \Aws\AutoScaling\AutoScalingClient
     */
    protected $asgClient;

    public function __construct($profile=null)
    {
        $this->asgClient = \AwsInspector\SdkFactory::getClient('AutoScaling', $profile);
    }

    public function findAutoScalingGroups()
    {
        $result = $this->asgClient->describeAutoScalingGroups();

        $rows = $result->search('AutoScalingGroups[]');

        $collection = new \AwsInspector\Model\Collection();
        foreach ($rows as $row) {
            $collection->attach(new AutoScalingGroup($row));
        }
        return $collection;
    }

    /**
     * @param array $tags
     * @return \AwsInspector\Model\Collection
     */
    public function findAutoScalingGroupsByTags(array $tags = array())
    {
        $collection = new \AwsInspector\Model\Collection();
        foreach ($this->findAutoScalingGroups() as $autoScalingGroup) { /* @var $autoScalingGroup AutoScalingGroup */
            if ($autoScalingGroup->matchesTags($tags)) {
                $collection->attach($autoScalingGroup);
            }
        }
        return $collection;
    }

    public function findByAutoScalingGroupName($regex)
    {
        $collection = new \AwsInspector\Model\Collection();
        foreach ($this->findAutoScalingGroups() as $autoScalingGroup) {  /* @var $autoScalingGroup AutoScalingGroup */
            if (preg_match($regex, $autoScalingGroup->getAutoScalingGroupName())) {
                $collection->attach($autoScalingGroup);
            }
        }
        return $collection;
    }

    public function findLaunchConfigurations()
    {
        $result = $this->asgClient->describeLaunchConfigurations();

        $rows = $result->search('LaunchConfigurations[]');

        $collection = new \AwsInspector\Model\Collection();
        foreach ($rows as $row) {
            $collection->attach(new LaunchConfiguration($row));
        }
        return $collection;
    }

    public function findLaunchConfigurationsGroupedByImageId()
    {
        $imageIds = [];
        foreach ($this->findLaunchConfigurations() as $launchConfiguration) { /* @var $launchConfiguration LaunchConfiguration */
            $imageId = $launchConfiguration->getImageId();
            if (!isset($imageIds[$imageId])) {
                $imageIds[$imageId] = [];
            }
            $imageIds[$imageId][] = $launchConfiguration;
        }
        return $imageIds;
    }

}