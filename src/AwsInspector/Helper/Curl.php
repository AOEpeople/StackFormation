<?php

namespace AwsInspector\Helper;

class Curl
{

    protected $url;
    protected $headers=[];
    protected $maxTime;
    protected $connection;
    protected $responseStatus;
    protected $responseHeaders = [];
    protected $responseBody;

    public function __construct($url, $headers=[], \AwsInspector\Ssh\Connection $connection=null, $maxTime=5)
    {
        $this->url = $url;
        $this->headers = $headers;
        $this->connection = is_null($connection) ? new \AwsInspector\Ssh\LocalConnection() : $connection;
        $this->maxTime = $maxTime;
        $this->doRequest();
    }

    protected function getHeaderParams() {
        $params=[];
        foreach ($this->headers as $header) {
            $params[] = "--header '$header'";
        }
        return implode(' ', $params);
    }

    protected function getCurlCommand($params=[]) {
        $command = [];
        $command[] = 'curl';
        $command[] = $this->getHeaderParams();
        $command[] = '--insecure';
        $command[] = '--silent';
        if ($this->maxTime) {
            $command[] = '--max-time '.$this->maxTime;
        }
        $command = array_merge($command, $params);
        return implode(' ', $command);
    }

    protected function parseHeaders($tmpfile) {
        $file = file($tmpfile);
        $this->responseStatus = trim(array_shift($file));
        foreach ($file as $line) {
            $line = trim($line);
            if (empty($line)) {
                return;
            }
            if (strpos($line, ':') === false) {
                throw new \Exception('Header without colon found: ' . $line);
            }
            list($headername, $headervalue) = explode(":", $line, 2);
            $this->responseHeaders[trim($headername)] = trim($headervalue);
        }
    }

    public function doRequest() {
        $tmpfile = tempnam(sys_get_temp_dir(), 'curl_headerdump_');
        $command = $this->getCurlCommand([
            '--dump-header ' . $tmpfile,
            escapeshellarg($this->url)
        ]);
        $result = $this->connection->exec($command);
        if ($result['returnVar'] != 0) {
            throw new \Exception('Curl error: ' . $this->getCurlError($result['returnVar']));
        }
        $this->parseHeaders($tmpfile);
        unlink($tmpfile);
        $this->responseBody = implode("\n", $result['output']);
        return $this;
    }

    public function getResponseStatus() {
        return $this->responseStatus;
    }

    public function getResponseHeaders() {
        return $this->responseHeaders;
    }

    public function getResponseCode() {
        $matches = [];
        preg_match('|HTTP/\d\.\d\s+(\d+)\s+.*|', $this->responseStatus, $matches);
        return $matches[1];
    }

    public function getResponseHeader($header) {
        if (!isset($this->responseHeaders[$header])) {
            throw new \Exception("Header '$header' not found.'");
        }
        return $this->responseHeaders[$header];
    }

    public function getResponseBody() {
        return $this->responseBody;
    }

    public function getCurleMap()
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

    public function getCurlError($exitCode)
    {
        $map = $this->getCurleMap();
        $errorMessage = isset($map[$exitCode]) ? $map[$exitCode] : 'undefined';
        $errorMessage .= ' (Code: ' . $exitCode . ')';
        return $errorMessage;
    }

}

