<?php

namespace AwsInspector\Model\ElastiCache;

/**
 * Class CacheCluster
 *
 * @method getCacheClusterId()
 * @method getClientDownloadLandingPage()
 * @method getCacheNodeType()
 * @method getEngine()
 * @method getEngineVersion()
 * @method getCacheClusterStatus()
 * @method getNumCacheNodes()
 * @method getCacheClusterCreateTime()
 * @method getPreferredMaintenanceWindow()
 * @method getPendingModifiedValues()
 * @method getCacheSecurityGroups()
 * @method getCacheParameterGroup()
 * @method getCacheParameterGroupName()
 * @method getParameterApplyStatus()
 * @method getCacheNodeIdsToReboot()
 * @method getCacheSubnetGroupName()
 * @method getAutoMinorVersionUpgrade()
 * @method getSecurityGroups()
 * @method getSnapshotRetentionLimit()
 * @method getSnapshotWindow()
 * @method getCacheNodes()
 */
class CacheCluster extends \AwsInspector\Model\AbstractResource
{

    protected $tags;

    protected $resourceName;

    public function getResourceName()
    {
        if (is_null($this->resourceName)) {

            // TODO: this should be changed!
            $region = getenv('HURRICANE_TEST_REGION');
            if (empty($region)) {
                throw new \Exception('Region missing');
            }

            // get account id from current user
            $iam = new \AwsInspector\Model\Iam\Repository();
            $accountId = $iam->findCurrentUser()->getAccountId();

            $parts = [];
            $parts['prefix'] = 'arn:aws:elasticache';
            $parts['region'] = $region;
            $parts['AccountId'] = $accountId;
            $parts['resourcetype'] = 'cluster';
            $parts['name'] = $this->getCacheClusterId();
            $this->resourceName = implode(':', $parts);
        }
        return $this->resourceName;
    }

    public function getTags()
    {
        if (is_null($this->tags)) {
            $elastiCacheClient = \AwsInspector\SdkFactory::getClient('ElastiCache');
            /* @var $elastiCacheClient \Aws\ElastiCache\ElastiCacheClient */
            $result = $elastiCacheClient->listTagsForResource(['ResourceName' => $this->getResourceName()]);
            $this->tags = $result->get('TagList');
        }
        return $this->tags;
    }
}


