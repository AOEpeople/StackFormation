<?php

namespace StackFormation\Command;

use Aws\CloudFormation\Exception\CloudFormationException;
use StackFormation\BlueprintFactory;
use StackFormation\Config;
use StackFormation\DependencyTracker;
use StackFormation\PlaceholderResolver;
use StackFormation\StackFactory;
use StackFormation\SdkFactory;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\ChoiceQuestion;

abstract class AbstractCommand extends Command
{
    /* @var BlueprintFactory */
    protected $blueprintFactory;

    /* @var StackFactory */
    protected $stackFactory;

    /* @var DependencyTracker */
    protected $dependencyTracker;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $cfnClient = SdkFactory::getCfnClient();
        $this->stackFactory = new StackFactory($cfnClient);
        $config = new Config();
        $this->dependencyTracker = new DependencyTracker();
        $placeholderResolver = new PlaceholderResolver($this->dependencyTracker, $this->stackFactory, $config);
        $this->blueprintFactory = new BlueprintFactory($cfnClient, $config, $placeholderResolver);
    }

    protected function interactAskForBlueprint(InputInterface $input, OutputInterface $output)
    {
        $blueprint = $input->getArgument('blueprint');
        if (empty($blueprint) || strpos($blueprint, '*') !== false || strpos($blueprint, '?') !== false) {

            $choices = $this->blueprintFactory->getBlueprintLabels($blueprint ? $blueprint : null);
            if (count($choices) == 0) {
                throw new \Exception('No matching blueprints found');
            } elseif (count($choices) == 1) {
                $blueprint = end($choices);
            } else {
                $helper = $this->getHelper('question');
                $question = new ChoiceQuestion('Please select a blueprint', $choices);

                $question->setErrorMessage('Blueprint %s is invalid.');

                $blueprint = $helper->ask($input, $output, $question);
            }
            $output->writeln('Selected blueprint: ' . $blueprint);

            list($stackName) = explode(' ', $blueprint);
            $input->setArgument('blueprint', $stackName);
        }

        return $blueprint;
    }

    protected function getStacks($nameFilter=null, $statusFilter=null)
    {
        return array_keys($this->stackFactory->getStacksFromApi(false, $nameFilter, $statusFilter));
    }

    public function interactAskForStack(InputInterface $input, OutputInterface $output, $nameFilter=null, $statusFilter=null)
    {
        $stack = $input->getArgument('stack');
        if (empty($stack)) {
            $choices = $this->getStacks($nameFilter, $statusFilter);

            if (count($choices) == 0) {
                throw new \Exception('No valid stacks found.');
            }
            if (count($choices) == 1) {
                $stack = end($choices);
            } else {

                $helper = $this->getHelper('question');
                $question = new ChoiceQuestion('Please select a stack', $choices);

                $question->setErrorMessage('Stack %s is invalid.');

                $stack = $helper->ask($input, $output, $question);
            }
            $output->writeln('Selected Stack: ' . $stack);

            $input->setArgument('stack', $stack);
        }

        return $stack;
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        try {
            return parent::run($input, $output);
        } catch (CloudFormationException $exception) {
            
            $message = \StackFormation\Helper::extractMessage($exception);
            if (strpos($message, 'No updates are to be performed.') !== false) {
                $output->writeln('No updates are to be performed.');

                return 0; // exit code
            } else {
                $formatter = new FormatterHelper();
                $formattedBlock = $formatter->formatBlock(['[CloudFormationException]', '', $message], 'error', true);
                $output->writeln("\n\n$formattedBlock\n\n");

                if ($output->isVerbose()) {
                    $output->writeln($exception->getTraceAsString());
                }

                return 1; // exit code
            }
        }
    }

}
