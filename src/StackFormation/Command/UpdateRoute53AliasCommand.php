<?php

namespace StackFormation\Command;

use StackFormation\Poller;
use StackFormation\Route53Manager;
use StackFormation\StackManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRoute53AliasCommand extends AbstractCommand
{

    protected $route53Manager;

    public function __construct($name = null)
    {
        $this->route53Manager = new Route53Manager();
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('r53:update-alias')
            ->setDescription('Update Route 53 Alias')
            ->addArgument(
                'elb',
                InputArgument::REQUIRED,
                'Elb (arn or {resource:...:...} syntax)'
            )
            ->addArgument(
                'zone',
                InputArgument::REQUIRED,
                'Hosted Zone (name or id)'
            )
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'DNS Name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $elb = $input->getArgument('elb');
        $hostedZone = $input->getArgument('zone');
        $name = $input->getArgument('name');

        $stackManager = new StackManager();
        $elb = $stackManager->resolvePlaceholders($elb);

        $output->writeln("Load Balancer: $elb");

        $changeId = $this->route53Manager->elb2Alias($elb, $hostedZone, $name);

        $output->writeln("Polling (Change Id: $changeId)");

        $result = '';
        Poller::poll(
            function () use ($changeId, $output, &$result) {
                $result = $this->route53Manager->getChange($changeId);
                $output->write('.');

                return ($result != 'PENDING');
            },
            5,
            20
        );

        $output->writeln("\nCompleted. Status: $result");

        if ($result != 'INSYNC') {
            return 1; // exit code
        }

        return 0; // exit code
    }
}
