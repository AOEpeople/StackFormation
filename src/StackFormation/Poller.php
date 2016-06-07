<?php

namespace StackFormation;

class Poller
{

    public static function poll($callback, $pollInterval = 10, $maxPolls = 50)
    {
        $first = true;
        do {
            if ($maxPolls-- < 0) {
                throw new \Exception('Max polls exceeded.');
            }
            if ($first) {
                $first = false;
            } else {
                sleep($pollInterval);
            }

            $result = $callback();
        } while (!$result);
        return $result;
    }
}
