<?php

namespace AwsInspector\Tests\Helper;

class CurlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $output
     * @param array $header
     * @param int $returnVar
     * @return \AwsInspector\Helper\Curl
     */
    public function getCurlObject(array $output = [], array $header = [], $returnVar = 0)
    {
        if (empty($output)) {
            $output = [
                '<HTML><HEAD><meta http-equiv="content-type" content="text/html;charset=utf-8">',
                '<TITLE>301 Moved</TITLE></HEAD><BODY>',
                '<H1>301 Moved</H1>',
                'The document has moved',
                '<A HREF="http://www.google.com/">here</A>.',
                '</BODY></HTML>',
                'HTTP/1.1 301 Moved Permanently',
                'Location: http://www.google.com/',
                'Content-Type: text/html; charset=UTF-8',
                ' ' // Implicit test of an empty line in parseHeader
            ];
        }

        $connectionMock = $this->getMockBuilder('\AwsInspector\Ssh\Connection')->disableOriginalConstructor()->getMock();
        $connectionMock->method('exec')->willReturn(['returnVar' => $returnVar, 'output' => $output]);
        $curl = new \AwsInspector\Helper\Curl('http://google.com', $header, $connectionMock);
        $curl->doRequest();
        return $curl;
    }

    /**
     * @test
     */
    public function parseHeaderThrowsExceptionIfColonIsMissing()
    {
        $this->setExpectedException('Exception', "Header without colon found: Line without colon");
        $curl = $this->getCurlObject([
            '<HTML><HEAD><meta http-equiv="content-type" content="text/html;charset=utf-8">',
            '<TITLE>301 Moved</TITLE></HEAD><BODY>',
            '<H1>301 Moved</H1>',
            'The document has moved',
            '<A HREF="http://www.google.com/">here</A>.',
            '</BODY></HTML>',
            'HTTP/1.1 301 Moved Permanently',
            'Location: http://www.google.com/',
            'Line without colon'
        ]);
    }

    /**
     * @test
     */
    public function doRequestThrowsExceptionIfReturnVarIsTrue()
    {
        $this->setExpectedException('Exception', "Curl error: CURLE_UNSUPPORTED_PROTOCOL (Code: 1)");
        $curl = $this->getCurlObject([], [], 1);
    }

    /**
     * @test
     */
    public function doRequestThrowsExceptionIfOutputIsNotSet()
    {
        $this->setExpectedException('Exception', "No output found");
        $connectionMock = $this->getMockBuilder('\AwsInspector\Ssh\Connection')->disableOriginalConstructor()->getMock();
        $connectionMock->method('exec')->willReturn(['returnVar' => 0]);
        $curl = new \AwsInspector\Helper\Curl('http://google.com', [], $connectionMock);
        $curl->doRequest();
    }

    /**
     * @test
     */
    public function getResponseHeaderReturnsExpectedHeader()
    {
        $curl = $this->getCurlObject();
        $this->assertSame('http://www.google.com/', $curl->getResponseHeader('Location'));
    }

    /**
     * @test
     */
    public function getResponseHeaderThrowsExceptionIfHeaderNotFound()
    {
        $this->setExpectedException('Exception', "Header 'DoesNotExist' not found.");
        $curl = $this->getCurlObject();
        $curl->getResponseHeader('DoesNotExist');
    }

    /**
     * @test
     */
    public function getResponseHeadersReturnsExpectedHeaderArray()
    {
        $curl = $this->getCurlObject();
        $this->assertSame(
            [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Location' => 'http://www.google.com/',
            ],
            $curl->getResponseHeaders()
        );
    }

    /**
     * @test
     */
    public function getResponseHeadersReturnsExpectedHeaderArrayWithNestedArray()
    {
        $curl = $this->getCurlObject([
            '<HTML><HEAD><meta http-equiv="content-type" content="text/html;charset=utf-8">',
            '<TITLE>301 Moved</TITLE></HEAD><BODY>',
            '<H1>301 Moved</H1>',
            'The document has moved',
            '<A HREF="http://www.google.com/">here</A>.',
            '</BODY></HTML>',
            'HTTP/1.1 301 Moved Permanently',
            'Location: http://www.google.com/',
            'X-StackFormation: Test',
            'X-StackFormation: Test2',
            'X-StackFormation: Test3'
        ]);

        $this->assertSame(
            [
                'X-StackFormation' => ['Test3', 'Test2', 'Test'],
                'Location' => 'http://www.google.com/'
            ],
            $curl->getResponseHeaders()
        );
    }

    /**
     * @test
     */
    public function getResponseStatusReturnsExpectedStatus()
    {
        $curl = $this->getCurlObject();
        $this->assertSame('HTTP/1.1 301 Moved Permanently', $curl->getResponseStatus());
    }

    /**
     * @test
     */
    public function getResponseCodeThrowsExceptionIfResponseCodeNotFound()
    {
        $this->setExpectedException('Exception', 'No response status found');
        $curl = $this->getCurlObject();
        $curl->setResponseCode('');
        $curl->getResponseCode();
    }

    /**
     * @test
     */
    public function getResponseCodeReturnsExpectedCode()
    {
        $curl = $this->getCurlObject();
        $this->assertSame('301', $curl->getResponseCode());
    }

    /**
     * @test
     */
    public function getResponseBodyReturnsExpectedValue()
    {
        $curl = $this->getCurlObject();
        $this->assertContains('<H1>301 Moved</H1>', $curl->getResponseBody());
    }

    /**
     * @test
     */
    public function getResponseBodyReturnsAlsoTheRestOfInvalidHeaderData()
    {
        $curl = $this->getCurlObject([
            '<HTML><HEAD><meta http-equiv="content-type" content="text/html;charset=utf-8">',
            '<TITLE>301 Moved</TITLE></HEAD><BODY>',
            '<H1>301 Moved</H1>',
            'The document has moved',
            '<A HREF="http://www.google.com/">here</A>.',
            '</BODY></HTML>',
            'StackFormationHTTP/1.1 301 Moved Permanently',
            'Location: http://www.google.com/'
        ]);
        $this->assertContains('StackFormation', $curl->getResponseBody());
    }

    /**
     * @test
     */
    public function getCurlCommandIncludesHeaderOption()
    {
        $void = $this->getCurlObject([], ['test' => 1]);
    }
}
