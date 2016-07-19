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
        $cloudWatchLogClient = \AwsInspector\SdkFactory::getClient('CloudWatchLogs');
        /* @var $cloudWatchLogClient \Aws\CloudWatchLogs\CloudWatchLogsClient */
        $params = [];
        if ($logGroupNamePrefix) {
            $params['logGroupNamePrefix'] = $logGroupNamePrefix;
        }
        $resGroups = $cloudWatchLogClient->describeLogGroups($params);
        foreach ($resGroups->search('logGroups[].logGroupName') as $logGroupName) {
            $resStreams = $cloudWatchLogClient->describeLogStreams([
                'logGroupName' => $logGroupName,
                'orderBy' => 'LastEventTime'
            ]);
            foreach ($resStreams->search('logStreams[].logStreamName') as $logStreamName) {
                if ($stream == $logStreamName) {
                    return $logGroupName;
                }
            }
        }
        return null;
    }
}
