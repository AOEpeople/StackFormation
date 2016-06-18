<?php

namespace AwsInspector\Model\SecurityGroup;

class Repository {

    /**
     * @param $groupId
     * @return SecurityGroup
     */
    public function findSecurityGroupByGroupId($groupId) {
        $group = $this->findSecurityGroups([['Name' => 'group-id', 'Values' => [$groupId]]])->getFirst();
        return $group;
    }

    /**
     * @param array $filters
     * @return \AwsInspector\Model\Collection
     * @throws \Exception
     */
    public function findSecurityGroups(array $filters=[]) {
        $ec2Client = \AwsInspector\SdkFactory::getClient('ec2'); /* @var $ec2Client \Aws\Ec2\Ec2Client */
        $result = $ec2Client->describeSecurityGroups(['Filters' => $filters]);
        $rows = $result->search('SecurityGroups[]');

        $collection = new \AwsInspector\Model\Collection();
        foreach ($rows as $row) {
            $securityGroup = new SecurityGroup($row);
            if ($securityGroup !== false) {
                $collection->attach($securityGroup);
            }
        }
        return $collection;
    }

    /**
     * @param array $tags
     * @return \AwsInspector\Model\Collection
     */
    public function findSecurityGroupsByTags(array $tags=array()) {
        foreach ($tags as $tagName => $tagValue) {
            $filters[] = ['Name' => 'tag-key', "Values" => [$tagName]];
            $filters[] = ['Name' => 'tag-value', "Values" => [$tagValue]];
        }
        return $this->findSecurityGroups($filters);
    }

}