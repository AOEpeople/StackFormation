<?php

namespace Sfn\Command\Stack;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('stack:deploy')
            ->setDescription('Deploy Stack')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');
        $output->writeln('Hello '.$stack);
    }

}