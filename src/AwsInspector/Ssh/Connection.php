<?php

namespace AwsInspector\Ssh;
use AwsInspector\Registry;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Connection
 *
 * @package AwsInspector\Ssh
 *
 * @author Fabrizio Branca
 */
class Connection
{

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var PrivateKey
     */
    protected $privateKey;

    /**
     * @var bool
     */
    protected $multiplex;

    /**
     * @var \AwsInspector\Model\Ec2\Instance
     */
    protected $jumpHost;

    /**
     * @var array
     */
    protected static $multiplexedConnections = [];

    /**
     * Connection constructor.
     *
     * @param $username
     * @param $host
     * @param PrivateKey|null $privateKey
     * @param \AwsInspector\Model\Ec2\Instance|null $jumpHost
     * @param bool $multiplex
     */
    public function __construct($username, $host, PrivateKey $privateKey = null, \AwsInspector\Model\Ec2\Instance $jumpHost = null, $multiplex=false)
    {
        if (empty($username)) {
            throw new \InvalidArgumentException("Username can't be empty");
        }
        if (empty($host)) {
            throw new \InvalidArgumentException("Host can't be empty");
        }
        $this->username = $username;
        $this->host = $host;
        $this->privateKey = $privateKey;
        $this->jumpHost = $jumpHost;
        $this->multiplex = $multiplex;
    }

    public function __toString()
    {
        $parts = ['ssh'];

        if ($this->privateKey) {
            $parts[] = '-i ' . $this->privateKey->getPrivateKeyFile();
        }

        if (!is_null($this->jumpHost)) {
            if ($output = Registry::get('output')) { /* @var $output OutputInterface */
                $output->writeln("[Using jump host: " . $this->jumpHost->getDefaultUsername() . '@' . $this->jumpHost->getPublicIpAddress() . "]");
            }
            $proxyCommand = new Command($this->jumpHost->getSshConnection(), 'nc %h %p');
            $parts[] = '-o ProxyCommand="' . $proxyCommand->__toString() . '"';
        }

        if ($this->multiplex) {
            $connection = "~/mux_{$this->username}@{$this->host}:22";
            self::$multiplexedConnections[$connection] = "$connection {$this->host}";
            $parts[] = "-o ControlPersist=yes -o ControlMaster=auto -S $connection";
        }

        $parts[] = '-o ConnectTimeout=5';
        $parts[] = '-o LogLevel=ERROR';
        $parts[] = '-o StrictHostKeyChecking=no';
        $parts[] = '-o UserKnownHostsFile=/dev/null'; // avoid "WARNING: REMOTE HOST IDENTIFICATION HAS CHANGED!"
        // $parts[] = '-t'; // Force pseudo-tty allocation.
        $parts[] = "{$this->username}@{$this->host}";

        return implode(' ', $parts);
    }

    /**
     * Close all multiplexed connections
     */
    public static function closeMuxConnections()
    {
        $count = count(self::$multiplexedConnections);
        if ($count) {
            echo "Closing $count multiplexed connections...\n";
            foreach (self::$multiplexedConnections as $key => $connection) {
                exec("ssh -O stop -o LogLevel=QUIET -S $connection > /dev/null 2>&1");
                unset(self::$multiplexedConnections[$key]);
            }
        }
    }

    /**
     * Execute command on this connection
     *
     * @param string $command
     * @param string $asUser
     * @return array
     */
    public function exec($command, $asUser=null)
    {
        $command = new Command($this, $command, $asUser);
        return $command->exec();
    }

    /**
     * Interactive connection
     */
    public function connect()
    {
        $descriptorSpec = [0 => STDIN, 1 => STDOUT, 2 => STDERR];
        $pipes = [];
        $process = proc_open($this->__toString(), $descriptorSpec, $pipes);
        if (is_resource($process)) {
            proc_close($process);
        }
    }

    /**
     * Interactive connection
     */
    public function tunnel($configuration)
    {
        if (count(explode(':', $configuration)) == 4) {
            list($localIp, $localPort, $remoteHost, $remotePort) = explode(':', $configuration);

            if (empty($localIp)) {
                throw new \InvalidArgumentException('Invalid local host');
            }
        } else {
            list($localPort, $remoteHost, $remotePort) = explode(':', $configuration);
        }

        if (!ctype_digit($localPort)) {
            throw new \InvalidArgumentException('Invalid local port');
        }
        if (empty($remoteHost)) {
            throw new \InvalidArgumentException('Invalid remote host');
        }
        if (!ctype_digit($remotePort)) {
            throw new \InvalidArgumentException('Invalid remote port');
        }

        $descriptorSpec = [0 => STDIN, 1 => STDOUT, 2 => STDERR];
        $pipes = [];
        $command = $this->__toString() . ' -L ' . $configuration . ' -N';
        $process = proc_open($command, $descriptorSpec, $pipes);
        if (is_resource($process)) {
            proc_close($process);
        }
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return PrivateKey
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * @return boolean
     */
    public function isMultiplex()
    {
        return $this->multiplex;
    }

    /**
     * @return \AwsInspector\Model\Ec2\Instance
     */
    public function getJumpHost()
    {
        return $this->jumpHost;
    }

}
