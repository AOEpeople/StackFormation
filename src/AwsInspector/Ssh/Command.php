<?php

namespace AwsInspector\Ssh;

class Command {

    protected $command;
    protected $connection;
    protected $asUser;

    /**
     * Command constructor.
     *
     * @param Connection $connection
     * @param string $command
     * @param string $asUser
     */
    public function __construct(Connection $connection, $command, $asUser=null)
    {
        $this->connection = $connection;
        if (!is_string($command)) {
            throw new \InvalidArgumentException("Command must be a string.");
        }
        $this->command = $command;
        $this->asUser = $asUser;
    }

    protected function getCommandString()
    {
        $command = $this->command;
        if ($this->asUser) {
            $command = sprintf('sudo -u %s bash -c %s',
                escapeshellarg($this->asUser),
                escapeshellarg($command)
            );
        }
        return $command;
    }

    public function __toString()
    {
        if (empty($this->connection->__toString())) {
            return $this->getCommandString();
        }
        return sprintf(
            "%s %s",
            $this->connection,
            escapeshellarg($this->getCommandString())
        );
    }

    public function exec()
    {
        // file_put_contents('/tmp/exec.log', $this->__toString() . "\n\n", FILE_APPEND);
        $returnVar = null;
        exec($this->__toString(), $output, $returnVar);
        return [
            'output' => $output,
            'returnVar' => $returnVar
        ];
    }

}