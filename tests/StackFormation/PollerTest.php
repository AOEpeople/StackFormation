<?php

namespace StackFormation\Tests;

class PollerTest extends \PHPUnit_Framework_TestCase
{
    protected $counters = [];

    /**
     * @test
     */
    public function poll()
    {
        $function = function() {
            if (!isset($this->counters[__METHOD__])) { $this->counters[__METHOD__] = 0; }
            return $this->counters[__METHOD__]++ > 5;
        };
        \StackFormation\Poller::poll($function, 0);
    }

    /**
     * @test
     */
    public function pollExceed()
    {
        $this->setExpectedException('Exception', 'Max polls exceeded.');
        $function = function() {
            if (!isset($this->counters[__METHOD__])) { $this->counters[__METHOD__] = 0; }
            return $this->counters[__METHOD__]++ > 51;
        };
        \StackFormation\Poller::poll($function, 0);
    }

    /**
     * @test
     */
    public function returnValueFromCallback()
    {
        $function = function() {
            if (!isset($this->counters[__METHOD__])) { $this->counters[__METHOD__] = 0; }
            return ($this->counters[__METHOD__]++ > 5) ? "HELLO WORLD" : false;
        };
        $result = \StackFormation\Poller::poll($function, 0);
        $this->assertEquals("HELLO WORLD", $result);
    }
}
