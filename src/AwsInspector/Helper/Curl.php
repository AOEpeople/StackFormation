<?php

namespace AwsInspector\Helper;

class Curl
{

    protected $url;
    protected $headers=[];
    protected $maxTime = 5;

    public function __construct($url, $headers=[])
    {
        $this->url = $url;
        $this->headers = $headers;
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

    public function getResponseStatusCodeCommand() {
        return $this->getCurlCommand([
            '--output /dev/null',
            '--write-out "%{http_code}"',
            escapeshellarg($this->url)
        ]);
    }

    public function getResponseHeadersCommand() {
        return $this->getCurlCommand([
            '--head',
            escapeshellarg($this->url)
        ]);
    }

    public function getResponseBodyCommand() {
        return $this->getCurlCommand([
            escapeshellarg($this->url)
        ]);
    }

}

