<?php

namespace AwsInspector\Model\CloudWatchLogs;

/**
 * Class LogGroup
 *
 * @method getLogGroupName()
 * @method getCreationTime()
 * @method getMetricFilterCount()
 * @method getArn()
 * @method getStoredBytes()
 */
class LogGroup extends \AwsInspector\Model\AbstractResource
{
    public function __construct(array $apiData, \StackFormation\Profile\Manager $profileManager = null)
    {
        $normalizedApiData = [];
        foreach ($apiData as $key => $value) {
            $normalizedApiData[ucfirst($key)] = $value;
        }
        parent::__construct($normalizedApiData, $profileManager);
    }
}
