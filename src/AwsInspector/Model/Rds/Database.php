<?php

namespace AwsInspector\Model\Rds;

/**
 * Class Database
 *
 * @method getDBInstanceIdentifier()
 * @method getDBInstanceClass()
 * @method getEngine()
 * @method getDBInstanceStatus()
 * @method getMasterUsername()
 * @method getDBName()
 * @method getEndpoint()
 * @method getAllocatedStorage()
 * @method getInstanceCreateTime()
 * @method getPreferredBackupWindow()
 * @method getBackupRetentionPeriod()
 * @method getDBSecurityGroups()
 * @method getVpcSecurityGroups()
 * @method getDBParameterGroups()
 * @method getAvailabilityZone()
 * @method getDBSubnetGroup()
 * @method getPreferredMaintenanceWindow()
 * @method getPendingModifiedValues()
 * @method getLatestRestorableTime()
 * @method getMultiAZ()
 * @method getEngineVersion()
 * @method getAutoMinorVersionUpgrade()
 * @method getReadReplicaDBInstanceIdentifiers()
 * @method getLicenseModel()
 * @method getOptionGroupMemberships()
 * @method getPubliclyAccessible()
 * @method getStorageType()
 * @method getDbInstancePort()
 * @method getStorageEncrypted()
 * @method getDbiResourceId()
 * @method getCACertificateIdentifier()
 * @method getCopyTagsToSnapshot()
 */
class Database extends \AwsInspector\Model\AbstractResource
{

    protected $tags;

    protected $resourceName;

    public function getResourceName()
    {
        if (is_null($this->resourceName)) {

            // get account id from current user
            $iam = new \AwsInspector\Model\Iam\Repository();
            $accountId = $iam->findCurrentUser()->getAccountId();

            $parts = [];
            $parts['prefix'] = 'arn:aws:rds';
            $parts['region'] = substr($this->getAvailabilityZone(), 0, -1);
            $parts['AccountId'] = $accountId;
            $parts['resourcetype'] = 'db';
            $parts['name'] = $this->getDBInstanceIdentifier();
            $this->resourceName = implode(':', $parts);
        }
        return $this->resourceName;
    }

    public function getTags()
    {
        if (is_null($this->tags)) {
            $rdsClient = \AwsInspector\SdkFactory::getClient('Rds');
            /* @var $rdsClient \Aws\Rds\RdsClient */
            $result = $rdsClient->listTagsForResource(['ResourceName' => $this->getResourceName()]);
            $this->tags = $result->get('TagList');
        }
        return $this->tags;
    }

}
