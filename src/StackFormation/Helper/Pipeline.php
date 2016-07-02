<?php

namespace StackFormation\Helper;

/**
 * Class Pipeline
 */
class Pipeline
{
    /**
     * @var callable[]
     */
    protected $stages = [];

    /**
     * Add stage
     *
     * @param callable $stage
     * @return $this
     */
    public function addStage(callable $stage)
    {
        $this->stages[] = $stage;
        return $this;
    }

    /**
     * Process the payload.
     *
     * @param $payload
     * @return mixed
     */
    public function process($payload)
    {
        foreach ($this->stages as $stage) {
            $payload = call_user_func($stage, $payload);
        }
        return $payload;
    }
}
