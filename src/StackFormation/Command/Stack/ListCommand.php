<?php

namespace StackFormation\Command\Stack;

use StackFormation\Stack;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:list')
            ->setDescription('List all stacks')
            ->addOption(
                'nameFilter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Name Filter (regex). Example --nameFilter \'/^foo/\''
            )
            ->addOption(
                'statusFilter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Name Filter (regex). Example --statusFilter \'/IN_PROGRESS/\''
            );
    }

    /**
     * Render the tree and return it as a string.
     *
     * @return string|null
     */
    public function render(array $data)
    {
        $output = '';
        $treeIterator = new \RecursiveTreeIterator(new \RecursiveArrayIterator($data), \RecursiveTreeIterator::SELF_FIRST);
        foreach ($treeIterator as $val) {
            $val = str_replace('-Array', '-\\', $val);
            $output .= $val . "\n";
        }
        return $output;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $nameFilter = $input->getOption('nameFilter');
        $statusFilter = $input->getOption('statusFilter');

        $stacks = $this->stackFactory->getStacksFromApi(false, $nameFilter, $statusFilter);
        $data = [];
        foreach ($stacks as $stackName => $stack) { /* @var $stack Stack */
            $stackNameParts = explode('-', $stackName, 5);
            $c = count($stackNameParts);
            if ($c == 1) { $data[$stackNameParts[0]] = $stackName; }
            if ($c == 2) { $data[$stackNameParts[0]][$stackNameParts[1]] = $stackName; }
            if ($c == 3) { $data[$stackNameParts[0]][$stackNameParts[1]][$stackNameParts[2]] = $stackName; }
            if ($c == 4) { $data[$stackNameParts[0]][$stackNameParts[1]][$stackNameParts[2]][$stackNameParts[3]] = $stackName; }
            if ($c >= 5) { $data[$stackNameParts[0]][$stackNameParts[1]][$stackNameParts[2]][$stackNameParts[3]][$stackNameParts[4]] = $stackName; }
        }

        $output->writeln($this->render($data));

        return;

        $nameFilter = $input->getOption('nameFilter');
        $statusFilter = $input->getOption('statusFilter');

        $stacks = $this->stackFactory->getStacksFromApi(false, $nameFilter, $statusFilter);

        $rows = [];
        foreach ($stacks as $stackName => $stack) { /* @var $stack Stack */
            $rows[] = [$stackName, \StackFormation\Helper::decorateStatus($stack->getStatus())];
        }

        $table = new Table($output);
        $table
            ->setHeaders(['Name', 'Status'])
            ->setRows($rows);
        $table->render();
    }
}
