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
        $this->interactAskForStack($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $this->stackFactory->getStack($input->getArgument('stack'));
        $blueprint = $this->blueprintFactory->getBlueprintByStack($stack);
        
        if (empty($blueprint)) {
            throw new \Exception('Could not find blueprint for stack ' . $stack);
        }

        $parametersStack = $this->stackManager->getParameters($stack);
        $parametersBlueprint = $this->stackManager->getBlueprintParameters($blueprint, true, true);


        $formatter = new FormatterHelper();
        $output->writeln("\n" . $formatter->formatBlock(['Parameters:'], 'error', true) . "\n");

        $returnVar = $this->printDiff(
            $this->arrayToString($parametersStack),
            $this->arrayToString($parametersBlueprint)
        );
        if ($returnVar == 0) {
            $output->writeln('No changes'."\n");
        }

        $formatter = new FormatterHelper();
        $output->writeln("\n" . $formatter->formatBlock(['Template:'], 'error', true) . "\n");

        $templateStack = trim($this->stackManager->getTemplate($stack));
        $templateBlueprint = trim($this->stackManager->getPreprocessedTemplate($blueprint));

        $templateStack = $this->normalizeJson($templateStack);
        $templateBlueprint = $this->normalizeJson($templateBlueprint);

        $returnVar = $this->printDiff(
            $templateStack,
            $templateBlueprint
        );
        if ($returnVar == 0) {
            $output->writeln('No changes'."\n");
        }
    }

}
