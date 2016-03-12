<?php

namespace StackFormation\Command\Blueprint;

use Aws\CloudFormation\Exception\CloudFormationException;
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
            ->setName('blueprint:compare-all')
            ->setDescription('Compare all local blueprints with the corresponding live stack');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $localStacks = $this->stackManager->getConfig()->getStacknames();

        $data = [];
        foreach ($localStacks as $localStack) {
            $error = false;

            try {
                $effectiveStackName = $this->stackManager->getConfig()->getEffectiveStackName($localStack, true);
            } catch (\Exception $e) {
                $error = true;
                $effectiveStackName = '[' . $e->getMessage() . ']';
            }

            // skip stacks with dynamic names
            if ($localStack != $effectiveStackName) {
                continue;
            }

            $tmp = [];
            $tmp['stackName'] = $localStack;
            //$tmp['effectiveStackName'] = $effectiveStackName;

            if (!$error) {

                // parameters
                if (!$output->isQuiet()) {
                    $output->writeln($localStack. ': Comparing parameters');
                }
                try {
                    $parameters_live = $this->stackManager->getParameters($effectiveStackName);
                    $parameters_local = $this->stackManager->getParametersFromConfig($effectiveStackName, true, true);
                    if ($this->compareParameters($parameters_live, $parameters_local)) {
                        $tmp['parameters'] = "<fg=green>equal</>";
                    } else {
                        $tmp['parameters'] = "<fg=red>different</>";
                    }

                    // template
                    if (!$output->isQuiet()) {
                        $output->writeln($localStack. ': Comparing template');
                    }
                    $template_live = trim($this->stackManager->getTemplate($effectiveStackName));
                    $template_local = trim($this->stackManager->getPreprocessedTemplate($localStack));

                    $template_live = $this->normalizeJson($template_live);
                    $template_local = $this->normalizeJson($template_local);

                    if ($template_live === $template_local) {
                        $tmp['template'] = "<fg=green>equal</>";
                    } else {
                        $tmp['template'] = "<fg=red>different</>";
                    }
                } catch (CloudFormationException $e) {
                    $tmp['parameters'] = 'Stack not found';
                    $tmp['template'] = 'Stack not found';
                } catch (\Exception $e) {
                    $tmp['parameters'] = 'EXCEPTION';
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
        $table->setHeaders(['Blueprint / Stack' /*, 'Effective Stackname'*/, 'Parameters', 'Template']);
        $table->setRows($data);
        $table->render();

        $output->writeln('');
        $output->writeln("-> Run this to show a diff for a specific stack:");
        $output->writeln("{$GLOBALS['argv'][0]} blueprint:diff <stackName>");
        $output->writeln('');
        $output->writeln("-> Run this to update a live stack:");
        $output->writeln("{$GLOBALS['argv'][0]} blueprint:deploy -o <stackName>");
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
