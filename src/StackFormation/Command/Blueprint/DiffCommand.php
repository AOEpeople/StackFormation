<?php

namespace StackFormation\Command\Blueprint;

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
            ->setName('blueprint:diff')
            ->setDescription('Compare a local blueprints template and input parameters with the corresponding live stack')
            ->addArgument(
                'blueprint',
                InputArgument::REQUIRED,
                'Blueprint'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForBlueprint($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $blueprint = $input->getArgument('blueprint');

        $effectiveStackName = $this->stackManager->getConfig()->getEffectiveStackName($blueprint);

        $parameters_live = $this->stackManager->getParameters($effectiveStackName);
        $parameters_local = $this->stackManager->getParametersFromConfig($effectiveStackName, true, true);


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

        $template_live = trim($this->stackManager->getTemplate($effectiveStackName));
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
