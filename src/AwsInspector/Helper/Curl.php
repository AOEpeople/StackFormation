<?php

namespace AwsInspector\Helper;

class Curl
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $headers=[];

    /**
     * @var int
     */
    protected $maxTime;

    /**
     * @var \AwsInspector\Ssh\Connection|\AwsInspector\Ssh\LocalConnection|null
     */
    protected $connection;

    /**
     * @var string
     */
    protected $responseStatus;

    /**
     * @var array
     */
    protected $responseHeaders = [];

    /**
     * @var string
     */
    protected $responseBody;

    /**
     * @param string $url
     * @param array $headers
     * @param \AwsInspector\Ssh\Connection|null $connection
     * @param int $maxTime
     */
    public function __construct($url, $headers = [], \AwsInspector\Ssh\Connection $connection = null, $maxTime = 5)
    {
        $this->url = $url;
        $this->headers = $headers;
        $this->connection = is_null($connection) ? new \AwsInspector\Ssh\LocalConnection() : $connection;
        $this->maxTime = $maxTime;
    }

    /**
     * @return string
     */
    protected function getCurlCommand() {
        $command = [];
        $command[] = 'curl';
        foreach ($this->headers as $key => $header) {
            if (!is_int($key)) {
                throw new \InvalidArgumentException("Don't use an associative array. Pass headers like this: [ 'Host: myhost', 'X-Forwarded-Proto: https']");
            }
            $command[] = "--header '$header'";
        }
        $command[] = '--insecure';
        $command[] = '--silent';
        if ($this->maxTime) {
            $command[] = '--max-time '.$this->maxTime;
        }
        $command[] = '--dump-header /dev/stdout';
        $command[] = '--user-agent AwsInspectorCurl';
        $command[] = escapeshellarg($this->url);
        return implode(' ', $command);
    }

    /**
     * @param string $line
     * @throws \Exception
     */
    protected function parseHeader($line) {
        $line = trim($line);
        if (empty($line)) {
            return;
        }

        if (strpos($line, ':') === false) {
            throw new \Exception('Header without colon found: ' . $line);
        }
        list($headername, $headervalue) = explode(":", $line, 2);
        $headername = trim($headername);
        $headervalue = trim($headervalue);
        if (isset($this->responseHeaders[$headername])) {
            if (!is_array($this->responseHeaders[$headername])) {
                // convert to array
                $this->responseHeaders[$headername] = [ $this->responseHeaders[$headername] ];
            }
            $this->responseHeaders[$headername][] = $headervalue;
        } else {
            $this->responseHeaders[$headername] = $headervalue;
        }
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function doRequest() {
        $command = $this->getCurlCommand();
        $result = $this->connection->exec($command);
        if ($result['returnVar'] != 0) {
            throw new \Exception('Curl error: ' . $this->getCurlError($result['returnVar']));
        }
        if (!isset($result['output'])) {
            throw new \Exception('No output found');
        }

        // the command FIRST returns the body and THEN the headers (I tried many different ways and no matter
        // what's redirected to what curl alwyas seems to dump the headers last
        $httpLine = false;
        do {
            $line = array_pop($result['output']);

            // yes, this is really ugly...
            // I wish we'd be able to separate header and body in a cleaner way
            // but we can't do this with exec(), and proc_open also doesn't make things easier
            if (preg_match('|HTTP/\d\.\d\s+(\d+)\s+.*|', $line, $matches)) {
                $this->setResponseCode($matches[0]);
                $httpLine = true;

                // put the rest back since it belongs to the response body
                $restOfThatLine = preg_replace('|HTTP/\d\.\d\s+(\d+)\s+.*|', '', $line);
                if ($restOfThatLine) {
                    array_push($result['output'], $restOfThatLine);
                }
            }
            if (!$httpLine && !empty($line)) {
                $this->parseHeader($line);
            }
        } while(!$httpLine);

        $this->responseBody = implode("\n", $result['output']);
        return $this;
    }

    /**
     * @return string
     */
    public function getResponseStatus() {
        return $this->responseStatus;
    }

    /**
     * @return array
     */
    public function getResponseHeaders() {
        return $this->responseHeaders;
    }

    /**
     * @param string $header
     * @return mixed
     * @throws \Exception
     */
    public function getResponseHeader($header) {
        if (!isset($this->responseHeaders[$header])) {
            throw new \Exception("Header '$header' not found.");
        }
        return $this->responseHeaders[$header];
    }

    /**
     * @param string $status
     */
    public function setResponseCode($status)
    {
        $this->responseStatus = $status;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getResponseCode() {
        if (empty($this->responseStatus)) {
            throw new \Exception('No response status found');
        }
        $matches = [];
        preg_match('|HTTP/\d\.\d\s+(\d+)\s+.*|', $this->responseStatus, $matches);
        return $matches[1];
    }

    /**
     * @return mixed
     */
    public function getResponseBody() {
        return $this->responseBody;
    }

    /**
     * @param string $exitCode
     * @return mixed|string
     */
    protected function getCurlError($exitCode)
    {
        $map = $this->getCurleMap();
        $errorMessage = isset($map[$exitCode]) ? $map[$exitCode] : 'undefined';
        $errorMessage .= ' (Code: ' . $exitCode . ')';
        return $errorMessage;
    }

    /**
     * @return array
     */
    protected function getCurleMap()
    {
        $map = [];
        $constants = get_defined_constants(true);
        $curlConstants = $constants['curl'];
        foreach ($curlConstants as $constant => $value) {
            if (strpos($constant, 'CURLE_') === 0) {
                $map[$value] = $constant;
            }
        }
        return $map;
    }
}
