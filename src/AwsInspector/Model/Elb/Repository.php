<?php

namespace AwsInspector\Model\Elb;

use AwsInspector\Model\Collection;

class Repository
{

    /**
     * @var \Aws\ElasticLoadBalancing\ElasticLoadBalancingClient
     */
    protected $elbClient;

    public function __construct()
    {
        $this->elbClient = \AwsInspector\SdkFactory::getClient('ElasticLoadBalancing');
    }

    public function findElbByName($name)
    {
        $result = $this->elbClient->describeLoadBalancers([ 'LoadBalancerNames' => [$name] ]);
        return new Elb($result->search('LoadBalancerDescriptions[0]'));
    }

    public function findElbs()
    {
        $result = $this->elbClient->describeLoadBalancers();
        $rows = $result->search('LoadBalancerDescriptions[]');

        $collection = new \AwsInspector\Model\Collection();
        foreach ($rows as $row) {
            $collection->attach(new Elb($row));
        }
        return $collection;
    }

    /**
     * @param array $tags
     * @return \AwsInspector\Model\Collection
     */
    public function findElbsByTags(array $tags = array())
    {
        $elbs = $this->findElbs();
        $matchingElbs = new Collection();
        foreach ($elbs as $elb) {
            /* @var $elb Elb */
            if ($elb->matchesTags($tags)) {
                $matchingElbs->attach($elb);
            }
        }
        return $matchingElbs;
    }

}