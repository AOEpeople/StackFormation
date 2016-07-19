<?php

namespace AwsInspector\Model\CloudWatchLogs;

use StackFormation\Helper\Finder;

class Repository
{
    /**
     * @var \Aws\CloudWatchLogs\CloudWatchLogsClient
     */
    protected $cloudWatchLogsClient;

    /**
     * @param null $profile
     * @param \StackFormation\Profile\Manager|null $profileManager
     */
    public function __construct($profile = null, \StackFormation\Profile\Manager $profileManager = null)
    {
        $this->profileManager = $profileManager ?: new  \StackFormation\Profile\Manager();
        $this->cloudWatchLogsClient = $this->profileManager->getClient('CloudWatchLogs', $profile);
    }

    /**
     * @param string $logGroupNameFilter
     * @return \AwsInspector\Model\Collection
     */
    public function findLogGroups($logGroupNameFilter=null)
    {
        $collection = new \AwsInspector\Model\Collection();
        $nextToken = null;
        do {
            $params = ['limit' => 50];
            if ($nextToken) {
                $params['nextToken'] = $nextToken;
            }
            $result = $this->cloudWatchLogsClient->describeLogGroups($params);
            foreach ($result->get('logGroups') as $row) {
                if (!$logGroupNameFilter || Finder::matchWildcard($logGroupNameFilter, $row['logGroupName'])) {
                    $collection->attach(new LogGroup($row));
                }
            }
            $nextToken = $result->get("nextToken");
        } while ($nextToken);
        return $collection;
    }

    /**
     * @param $logGroupName
     * @param string $logStreamNameFilter
     * @return \AwsInspector\Model\Collection
     */
    public function findLogStreams($logGroupName, $logStreamNameFilter=null)
    {
        if (empty($logGroupName)) {
            throw new \InvalidArgumentException('LogGroupName cannot be empty');
        }
        $result = $this->cloudWatchLogsClient->describeLogStreams([
            'logGroupName' => $logGroupName,
            'orderBy' => 'LastEventTime',
            'descending' => true
        ]);
        $rows = $result->search('logStreams[]');
        $collection = new \AwsInspector\Model\Collection();
        foreach ($rows as $row) {
            if (!$logStreamNameFilter || Finder::matchWildcard($logStreamNameFilter, $row['logStreamName'])) {
                $collection->attach(new LogStream($row));
            }
        }
        return $collection;
    }

    public function findLogEvents($logGroupName, $logStreamName, &$nextForwardToken)
    {
        $params = [
            'limit' => 50,
            'logGroupName' => $logGroupName,
            'logStreamName' => $logStreamName
        ];
        if ($nextForwardToken) {
            $params['nextToken'] = $nextForwardToken;
        }
        $res = $this->cloudWatchLogsClient->getLogEvents($params);
        $nextForwardToken = $res->get('nextForwardToken');
        return $res->search('events[].message');
    }

    public function deleteLogGroup($logGroupName)
    {
        $this->cloudWatchLogsClient->deleteLogGroup(['logGroupName' => $logGroupName]);
    }

}
