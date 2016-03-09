<?php

namespace StackFormation\Command;

use Aws\CloudFormation\Exception\CloudFormationException;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompareAllCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:compare-all')
            ->setDescription('Compare all local stacks with the corresponding live stack');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $localStacks = $this->stackManager->getConfig()->getStacknames();

        $data = [];
        foreach ($localStacks as $localStack) {
            $error = false;
            $tmp['stackName'] = $localStack;
            try {
                $effectiveStackName = $this->stackManager->getConfig()->getEffectiveStackName($localStack, true);
            } catch (\Exception $e) {
                $error = true;
                $effectiveStackName = '[' . $e->getMessage() . ']';
            }
            $tmp['effectiveStackName'] = $effectiveStackName;

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
                    $tmp['parameters'] = 'live does not exist';
                    $tmp['template'] = 'live does not exist';
                }
            } else {
                $tmp['parameters'] = '';
                $tmp['template'] = '';
            }

            $data[] = $tmp;

        }

        $output->writeln('');

        $table = new Table($output);
        $table->setHeaders(['Stackname', 'Effective Stackname', 'Parameters', 'Template']);
        $table->setRows($data);
        $table->render();

        $output->writeln('');
        $output->writeln("-> Run this to show a diff for a specific stack:");
        $output->writeln("{$GLOBALS['argv'][0]} stack:diff <stackName>");
        $output->writeln('');
        $output->writeln("-> Run this to update a live stack:");
        $output->writeln("{$GLOBALS['argv'][0]} stack:deploy -o <stackName>");
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
