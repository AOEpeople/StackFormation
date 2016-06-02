<?php

namespace StackFormation\Command\Stack\Show;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OutputsCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:show:outputs')
            ->setDescription('Show a live stack\'s outputs')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack'
            )
            ->addArgument(
                'key',
                InputArgument::OPTIONAL,
                'key'
            );;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForLiveStack($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');

        $data = $this->stackManager->getOutputs($stack);

        $key = $input->getArgument('key');
        if ($key) {
            if (!isset($data[$key])) {
                throw new \InvalidArgumentException("Could not find '$key'.");
            }
            $output->writeln($data[$key]);
            return;
        }

        $rows = [];
        foreach ($data as $k => $v) {
            $v = strlen($v) > 100 ? substr($v, 0, 100) . "..." : $v;
            $rows[] = [$k, $v];
        }

        $table = new Table($output);
        $table->setHeaders(['Key', 'Value'])
            ->setRows($rows)
            ->render();
    }
}
