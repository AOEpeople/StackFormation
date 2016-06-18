<?php

namespace AwsInspector\Model\Ebs;

class Repository {

    /**
     * @param $instanceId
     * @return \AwsInspector\Model\Collection
     */
    public function findEbsVolumesByInstanceId($instanceId) {
        return $this->findEbsVolumes([[
            'Name' => 'attachment.instance-id',
            'Values' => [$instanceId]
        ]]);
    }

    /**
     * @param array $filters
     * @return \AwsInspector\Model\Collection
     * @throws \Exception
     */
    public function findEbsVolumes(array $filters=[]) {
        $ec2Client = \AwsInspector\SdkFactory::getClient('ec2'); /* @var $ec2Client \Aws\Ec2\Ec2Client */
        $result = $ec2Client->describeVolumes(['Filters' => $filters]);
        $rows = $result->search('Volumes[]');

        $collection = new \AwsInspector\Model\Collection();
        foreach ($rows as $row) {
            $collection->attach(new Volume($row));
        }
        return $collection;
    }

    /**
     * @param array $tags
     * @return \AwsInspector\Model\Collection
     */
    public function findEbsVolumesByTags(array $tags=array()) {
        foreach ($tags as $tagName => $tagValue) {
            $filters[] = ['Name' => 'tag:'.$tagName, "Values" => [$tagValue]];
        }
        return $this->findEbsVolumes($filters);
    }

}