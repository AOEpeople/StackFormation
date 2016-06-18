<?php

namespace AwsInspector\Model\Ec2;

class Repository {

    /**
     * @param $value
     * @return false|Instance
     */
    public function findEc2Instance($value) {
        foreach (['instance-id', 'ip-address', 'private-ip-address'] as $field) {
            $instance = $this->findEc2InstanceBy($field, $value);
            if ($instance !== false) {
                return $instance;
            }
        }
        return false;
    }

    /**
     * @param $field
     * @param $value
     * @return false|Instance
     */
    public function findEc2InstanceBy($field, $value) {
        if (!in_array($field, ['instance-id', 'ip-address', 'private-ip-address'])) {
            throw new \InvalidArgumentException('Invalid field');
        }
        $filters = [
            ['Name' => 'instance-state-name', "Values" => ['running']],
            ['Name' => $field, "Values" => [$value]]
        ];
        $instanceCollection = $this->findEc2Instances($filters);
        if (count($instanceCollection) == 1) {
            return $instanceCollection->getFirst();
        }
        return false;
    }

    /**
     * @param array $filters
     * @return \AwsInspector\Model\Collection
     * @throws \Exception
     */
    public function findEc2Instances(array $filters=[]) {
        $ec2Client = \AwsInspector\SdkFactory::getClient('ec2'); /* @var $ec2Client \Aws\Ec2\Ec2Client */
        $result = $ec2Client->describeInstances(['Filters' => $filters]);
        $rows = $result->search('Reservations[].Instances[]');

        $collection = new \AwsInspector\Model\Collection();
        foreach ($rows as $row) {
            $instance = Factory::create($row);
            if ($instance !== false) {
                $collection->attach($instance);
            }
        }
        return $collection;
    }

    /**
     * @param array $tags
     * @return \AwsInspector\Model\Collection
     */
    public function findEc2InstancesByTags(array $tags=array()) {
        $filters = [['Name' => 'instance-state-name', "Values" => ['running']]];
        foreach ($tags as $tagName => $tagValue) {
            $filters[] = ['Name' => 'tag:'.$tagName, "Values" => [$tagValue]];
        }
        return $this->findEc2Instances($filters);
    }

}