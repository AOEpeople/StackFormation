<?php

namespace AwsInspector\Model\ElastiCache;

use AwsInspector\Model\Collection;

class Repository
{

    /**
     * @var \Aws\ElastiCache\ElastiCacheClient
     */
    protected $elastiCacheClient;

    public function __construct()
    {
        $this->elastiCacheClient = \AwsInspector\SdkFactory::getClient('ElastiCache');
    }

    public function findCacheClusters()
    {
        $result = $this->elastiCacheClient->describeCacheClusters(['ShowCacheNodeInfo' => true]);
        $rows = $result->search('CacheClusters[]');

        $collection = new \AwsInspector\Model\Collection();
        foreach ($rows as $row) {
            $collection->attach(new CacheCluster($row));
        }
        return $collection;
    }

    /**
     * @param array $tags
     * @return \AwsInspector\Model\Collection
     */
    public function findCacheClustersByTags(array $tags = array())
    {
        $cacheClusters = $this->findCacheClusters();
        $matchingCacheClusters = new Collection();
        foreach ($cacheClusters as $cacheCluster) { /* @var $cacheCluster CacheCluster */
            if ($cacheCluster->matchesTags($tags)) {
                $matchingCacheClusters->attach($cacheCluster);
            }
        }
        return $matchingCacheClusters;
    }

}