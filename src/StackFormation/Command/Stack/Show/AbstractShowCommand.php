<?php

namespace StackFormation\Command\Stack\Show;

use StackFormation\Helper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractShowCommand extends \StackFormation\Command\AbstractCommand
{

    protected $property;

    protected function configure()
    {
        if (!in_array($this->property, ['resources', 'outputs', 'parameters'])) {
            throw new \Exception('Invalid property');
        }

        $this
            ->setName('stack:show:'.$this->property)
            ->setDescription('Show a live stack\'s '.$this->property)
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack'
            )
            ->addArgument(
                'key',
                InputArgument::OPTIONAL,
                'key'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForStack($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stackName = $input->getArgument('stack');
        Helper::validateStackname($stackName);
        $stack = $this->getStackFactory()->getStack($stackName);

        $methodName = 'get'.ucfirst($this->property);

        $key = $input->getArgument('key');
        if ($key) {
            $methodName = substr($methodName, 0, -1);
            $output->writeln($stack->$methodName($key));
            return;
        }

        $data = $stack->$methodName();

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
