<?php

namespace AwsInspector\Model\AutoScaling;

use AwsInspector\Model\Collection;

class Repository
{

    /**
     * @var \Aws\AutoScaling\AutoScalingClient
     */
    protected $asgClient;

    public function __construct()
    {
        $this->asgClient = \AwsInspector\SdkFactory::getClient('AutoScaling');
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
        $autoScalingGroups = $this->findAutoScalingGroups();
        $matchingElbs = new Collection();
        foreach ($autoScalingGroups as $autoScalingGroup) { /* @var $autoScalingGroup AutoScalingGroup */
            /* @var $autoScalingGroup AutoScalingGroup */
            if ($autoScalingGroup->matchesTags($tags)) {
                $matchingElbs->attach($autoScalingGroup);
            }
        }
        return $matchingElbs;
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

}