<?php

namespace StackFormation\Command\Stack;

use Aws\CloudFormation\Exception\CloudFormationException;
use StackFormation\Diff;
use StackFormation\Stack;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompareAllCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:compare-all')
            ->setDescription('Compare all live stacks with their corresponding blueprint');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stacks = $this->stackFactory->getStacksFromApi(false);

        $data = [];
        foreach ($stacks as $stackName => $stack) { /* @var $stack Stack */
            $tmp = [];
            $tmp['stackName'] = $stackName;
            $tmp['blueprintName'] = '';
            $tmp['parameters'] = '';
            $tmp['template'] = '';

            $diff = new Diff($output);

            try {
                $blueprint = $this->blueprintFactory->getBlueprintByStack($stack);
                $diff->setStack($stack);
                $diff->setBlueprint($blueprint);
                $tmp['blueprintName'] = $blueprint->getName();
                $tmp = array_merge($tmp, $diff->compare());
            } catch (\Exception $e) {
                $tmp['blueprintName'] = '<fg=red>Not found</>';
            }
            $data[] = $tmp;
        }

        $output->writeln('');

        $table = new Table($output);
        $table->setHeaders(['Stack', 'Blueprint', 'Parameters', 'Template']);
        $table->setRows($data);
        $table->render();

        $output->writeln('');
        $output->writeln("-> Run this to show a diff for a specific stack:");
        $output->writeln("{$GLOBALS['argv'][0]} stack:diff <stackName>");
        $output->writeln('');
        $output->writeln("-> Run this to update a live stack:");
        $output->writeln("{$GLOBALS['argv'][0]} blueprint:deploy -o <blueprintName>");
        $output->writeln('');
    }

}
