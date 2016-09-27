<?php

namespace StackFormation\Helper;

class Finder
{

    public static function matchWildcard($wildcard_pattern, $haystack)
    {
        $regex = str_replace(
            ["\*", "\?"],
            ['.*', '.'],
            preg_quote($wildcard_pattern, '/')
        );
        return preg_match('/^' . $regex . '$/is', $haystack);
    }

    public static function find($wildcardPatterns, array $choices)
    {
        if (!is_array($wildcardPatterns)) {
            $wildcardPatterns = [$wildcardPatterns];
        }
        $found = [];
        foreach ($choices as $choice) {
            foreach ($wildcardPatterns as $wildcardPattern) {
                if (self::matchWildcard($wildcardPattern, $choice)) {
                    $found[] = $choice;
                }
            }
        }
        $found = array_unique($found);
        return $found;
    }

    public static function findCloudWatchLogGroupByStream($stream, $logGroupNamePrefix = null)
    {
        // TODO: refactor this to use \AwsInspector\Model\CloudWatchLogs\Repository

        $cloudWatchLogClient = \AwsInspector\SdkFactory::getClient('CloudWatchLogs');
        /* @var $cloudWatchLogClient \Aws\CloudWatchLogs\CloudWatchLogsClient */

        $groupsNextToken = null;
        do {
            $params = [];
            if ($logGroupNamePrefix) {
                $params['logGroupNamePrefix'] = $logGroupNamePrefix;
            }
            if ($groupsNextToken) {
                $params['nextToken'] = $groupsNextToken;
            }
            $resGroups = $cloudWatchLogClient->describeLogGroups($params);
            foreach ($resGroups->search('logGroups[].logGroupName') as $logGroupName) {
                $streamsNextToken = null;
                do {
                    $streamsParams = [
                        'logGroupName' => $logGroupName,
                        'orderBy' => 'LastEventTime'
                    ];
                    if ($streamsNextToken) {
                        $streamsParams['nextToken'] = $streamsNextToken;
                    }
                    $resStreams = $cloudWatchLogClient->describeLogStreams($streamsParams);
                    foreach ($resStreams->search('logStreams[].logStreamName') as $logStreamName) {
                        if ($stream == $logStreamName) {
                            return $logGroupName;
                        }
                    }
                    $streamsNextToken = $resGroups->get("nextToken");
                } while ($streamsNextToken);
            }
            $groupsNextToken = $resGroups->get("nextToken");
        } while ($groupsNextToken);
        return null;
    }
}
