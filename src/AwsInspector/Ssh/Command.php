<?php

namespace AwsInspector\Ssh;

class Command {

    protected $command;
    protected $connection;

    /**
     * Command constructor.
     *
     * @param Connection $connection
     * @param string $command
     */
    public function __construct(Connection $connection, $command) {
        $this->connection = $connection;
        if (!is_string($command)) {
            throw new \InvalidArgumentException("Command must be a string.");
        }
        $this->command = $command;
    }

    public function __toString() {
        return sprintf(
            '%s %s',
            $this->connection,
            $this->command
        );
    }

    public function exec() {
        // file_put_contents('/tmp/exec.log', $this->__toString() . "\n", FILE_APPEND);
        $returnVar = null;
        exec($this->__toString(), $output, $returnVar);
        return [
            'output' => $output,
            'returnVar' => $returnVar
        ];
    }

}