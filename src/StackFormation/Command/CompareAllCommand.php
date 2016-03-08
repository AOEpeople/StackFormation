<?php

namespace StackFormation\Command;

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
                    $output->writeln('Comparing parameters for ' . $localStack);
                }
                $parameters_live = $this->stackManager->getParameters($effectiveStackName);
                $parameters_local = $this->stackManager->getParametersFromConfig($effectiveStackName, true, true);
                if ($this->arrayToString($parameters_live) === $this->arrayToString($parameters_local)) {
                    $tmp['parameters'] = "<fg=green>equal</>";
                } else {
                    $tmp['parameters'] = "<fg=red>different</>";
                }

                // template
                if (!$output->isQuiet()) {
                    $output->writeln('Comparing template for ' . $localStack);
                }
                $template_live = trim($this->stackManager->getTemplate($effectiveStackName));
                $template_local = trim($this->stackManager->getPreprocessedTemplate($localStack));
                if ($template_live === $template_local) {
                    $tmp['template'] = "<fg=green>equal</>";
                } else {
                    $tmp['template'] = "<fg=red>different</>";
                }
            } else {
                $tmp['parameters'] = '';
                $tmp['template'] = '';
            }

            $data[] = $tmp;

        }

        $table = new Table($output);
        $table->setHeaders(['Stackname', 'Effective Stackname', 'Parameters', 'Template']);
        $table->setRows($data);
        $table->render();
    }

    protected function arrayToString(array $a)
    {
        ksort($a);
        $lines = [];
        foreach ($a as $key => $value) {
            $lines[] = "$key: $value";
        }
        return implode("\n", $lines);
    }

}
