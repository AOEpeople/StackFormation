<?php

namespace AwsInspector\Model\CloudWatchLogs;

/**
 * Class LogStream
 *
 * @method getlogStreamName()
 * @method getCreationTime()
 * @method getFirstEventTimestamp()
 * @method getLastEventTimestamp()
 * @method getLastIngestionTime()
 * @method getUploadSequenceToken()
 * @method getArn()
 * @method getStoredBytes()
 */
class LogStream extends \AwsInspector\Model\AbstractResource
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
