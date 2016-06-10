<?php

namespace StackFormation\Command\Stack;

use Aws\CloudFormation\Exception\CloudFormationException;
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
            
            $error = false;

            try {
                $blueprintName = $stack->getBlueprintName();
            } catch (\Exception $e) {
                try {
                    // let's try if there's a blueprint with the same name
                    $blueprint = $this->blueprintFactory->getBlueprint($stackName);
                    $blueprintName = $blueprint->getName();
                } catch (\Exception $e) {
                    $error = true;
                    $blueprintName = '<fg=red>Not found</>';
                }
            }
            
            $tmp = [];
            $tmp['stackName'] = $stackName;
            $tmp['blueprintName'] = $blueprintName;

            if (!$error) {

                $blueprint = $this->blueprintFactory->getBlueprint($blueprintName);

                // parameters
                if (!$output->isQuiet()) {
                    $output->writeln($stackName. ': Comparing parameters');
                }
                try {
                    $parametersStack = $stack->getParameters();
                    $parametersBlueprint = $blueprint->getParameters(true, true);
                    if ($this->compareParameters($parametersStack, $parametersBlueprint)) {
                        $tmp['parameters'] = "<fg=green>equal</>";
                    } else {
                        $tmp['parameters'] = "<fg=red>different</>";
                    }

                    // template
                    if (!$output->isQuiet()) {
                        $output->writeln($stackName. ': Comparing template');
                    }
                    $templateStack = trim($stack->getTemplate());
                    $templateBlueprint = trim($blueprint->getPreprocessedTemplate());

                    $templateStack = $this->normalizeJson($templateStack);
                    $templateBlueprint = $this->normalizeJson($templateBlueprint);

                    if ($templateStack === $templateBlueprint) {
                        $tmp['template'] = "<fg=green>equal</>";
                    } else {
                        $tmp['template'] = "<fg=red>different</>";
                    }
                } catch (CloudFormationException $e) {
                    $tmp['parameters'] = 'Stack not found';
                    $tmp['template'] = 'Stack not found';
                } catch (\Exception $e) {
                    $tmp['parameters'] = 'EXCEPTION ' . $e->getMessage();
                    $tmp['template'] = 'EXCEPTION';
                }
            } else {
                $tmp['parameters'] = '';
                $tmp['template'] = '';
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

    protected function compareParameters(array $a, array $b)
    {
        // skip password fields
        while (($passWordKeyInA = array_search('****', $a)) !== false) {
            unset($a[$passWordKeyInA]);
            unset($b[$passWordKeyInA]);
        }
        while (($passWordKeyInB = array_search('****', $b)) !== false) {
            unset($a[$passWordKeyInB]);
            unset($b[$passWordKeyInB]);
        }
        return $this->arrayToString($a) == $this->arrayToString($b);
    }

}
