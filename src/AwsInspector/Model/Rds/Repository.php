<?php

namespace AwsInspector\Model\Rds;

use AwsInspector\Model\Collection;

class Repository
{

    /**
     * @var \Aws\Rds\RdsClient
     */
    protected $rdsClient;

    public function __construct()
    {
        $this->rdsClient = \AwsInspector\SdkFactory::getClient('Rds');
    }

    public function findDatabases()
    {
        $result = $this->rdsClient->describeDBInstances();
        $rows = $result->search('DBInstances[]');

        $collection = new \AwsInspector\Model\Collection();
        foreach ($rows as $row) {
            $collection->attach(new Database($row));
        }
        return $collection;
    }

    /**
     * @param array $tags
     * @return \AwsInspector\Model\Collection
     */
    public function findDatabasesByTags(array $tags = array())
    {
        $databases = $this->findDatabases();
        $matchingDatabases = new Collection();
        foreach ($databases as $database) {
            /* @var $database Database */
            try {
                if ($database->matchesTags($tags)) {
                    $matchingDatabases->attach($database);
                }
            } catch (\Aws\Rds\Exception\RdsException $e) {
                if ($e->getAwsErrorCode() != 'AccessDenied') {
                    throw $e;
                }
            }
        }
        return $matchingDatabases;
    }

}