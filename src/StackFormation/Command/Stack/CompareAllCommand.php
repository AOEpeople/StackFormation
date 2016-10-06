<?php

namespace StackFormation\Command\Stack;

use StackFormation\Diff;
use StackFormation\Exception\BlueprintNotFoundException;
use StackFormation\Exception\BlueprintReferenceNotFoundException;
use StackFormation\Helper;
use StackFormation\Helper\Div;
use StackFormation\Stack;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompareAllCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:compare-all')
            ->setDescription('Compare all live stacks with their corresponding blueprint')
            ->addOption(
                'nameFilter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Name Filter (regex). Example --nameFilter \'/^foo/\''
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $nameFilter = $input->getOption('nameFilter');
        $stacks = $this->getStackFactory()->getStacksFromApi(false, $nameFilter);

        $error = false;
        $data = [];
        foreach ($stacks as $stackName => $stack) { /* @var $stack Stack */

            $this->dependencyTracker->reset();

            $tmp = [];
            $tmp['stackName'] = $stackName;
            $tmp['status'] = Helper\Decorator::decorateStatus($stack->getStatus());
            $tmp['blueprintName'] = '';
            $tmp['parameters'] = '';
            $tmp['template'] = '';

            $diff = new Diff($output);

            try {
                $blueprint = $this->blueprintFactory->getBlueprintByStack($stack);
                $env = $stack->getUsedEnvVars();
                $diff->setStack($stack);
                $diff->setBlueprint($blueprint);
                $tmp['blueprintName'] = '<fg=green>' . $blueprint->getName().'</>';
                if (count($env)) {
                    $tmp['blueprintName'] .= "\n". wordwrap(Div::assocArrayToString($stack->getUsedEnvVars()), 80, "\n");
                }
                $tmp = array_merge($tmp, $diff->compare());

                if (isset($tmp['error']) && $tmp['error'] === true) {
                    $error = true;
                }
            } catch (BlueprintReferenceNotFoundException $e) {
                $tmp['blueprintName'] = '-';
                $error = true;
            } catch (BlueprintNotFoundException $e) {
                $tmp['blueprintName'] = '<fg=red>Not found: '.$e->getBlueprintName().'</>';
                $error = true;
            } catch (\Exception $e) {
                $tmp['blueprintName'] = '<fg=red>Exception: '.$e->getMessage().'</>';
                $error = true;
            }
            if (isset($tmp['error'])) {
                // so the column doesn't show on in the output table
                unset($tmp['error']);
            }
            $data[] = $tmp;
        }

        $output->writeln('');

        $table = new Table($output);
        $table->setHeaders(['Stack', 'Status', 'Blueprint', 'Parameters', 'Template']);
        $table->setRows($data);
        $table->render();

        $output->writeln('');
        $output->writeln("-> Run this to show a diff for a specific stack:");
        $output->writeln("{$GLOBALS['argv'][0]} stack:diff <stackName>");
        $output->writeln('');
        $output->writeln("-> Run this to update a live stack:");
        $output->writeln("{$GLOBALS['argv'][0]} blueprint:deploy -o <blueprintName>");
        $output->writeln('');

        if ($error === true) {
            exit(1);
        }
    }

}
