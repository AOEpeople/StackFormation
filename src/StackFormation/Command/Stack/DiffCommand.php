<?php

namespace StackFormation\Command\Stack;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DiffCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:diff')
            ->setDescription('Compare a stack\'s template and input parameters with its blueprint')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack name'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForLiveStack($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');
        $blueprint = $this->stackManager->getBlueprintNameForStack($stack);
        if (empty($blueprint)) {
            throw new \Exception('Could not find blueprint for stack ' . $stack);
        }

        $parameters_live = $this->stackManager->getParameters($stack);
        $parameters_local = $this->stackManager->getBlueprintParameters($blueprint, true, true);


        $formatter = new FormatterHelper();
        $output->writeln("\n" . $formatter->formatBlock(['Parameters:'], 'error', true) . "\n");

        $returnVar = $this->printDiff(
            $this->arrayToString($parameters_live),
            $this->arrayToString($parameters_local)
        );
        if ($returnVar == 0) {
            $output->writeln('No changes'."\n");
        }

        $formatter = new FormatterHelper();
        $output->writeln("\n" . $formatter->formatBlock(['Template:'], 'error', true) . "\n");

        $template_live = trim($this->stackManager->getTemplate($stack));
        $template_local = trim($this->stackManager->getPreprocessedTemplate($blueprint));

        $template_live = $this->normalizeJson($template_live);
        $template_local = $this->normalizeJson($template_local);

        $returnVar = $this->printDiff(
            $template_live,
            $template_local
        );
        if ($returnVar == 0) {
            $output->writeln('No changes'."\n");
        }
    }

}
