<?php

namespace StackFormation\Command;

use Aws\CloudFormation\Exception\CloudFormationException;
use StackFormation\BlueprintFactory;
use StackFormation\Config;
use StackFormation\DependencyTracker;
use StackFormation\Exception\StackNoUpdatesToBePerformedException;
use StackFormation\Helper;
use StackFormation\Helper\Exception;
use StackFormation\Profile\Manager;
use StackFormation\StackFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractCommand extends Command
{
    /**
     * @var BlueprintFactory
     */
    protected $blueprintFactory;

    /**
     * @var StackFactory
     */
    protected $stackFactory;

    /**
     * @var DependencyTracker
     */
    protected $dependencyTracker;

    /**
     * @var Manager
     */
    protected $profileManager;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->profileManager = new Manager(null, $output);
        $config = new Config();
        $this->dependencyTracker = new DependencyTracker();
        $this->blueprintFactory = new BlueprintFactory(
            $config,
            new \StackFormation\ValueResolver\ValueResolver($this->dependencyTracker, $this->profileManager, $config)
        );
    }

    protected function getStackFactory()
    {
        if (is_null($this->stackFactory)) {
            $this->stackFactory = $this->profileManager->getStackFactory(null);
        }
        return $this->stackFactory;
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
            list($blueprint) = explode(' ', $blueprint);
        } elseif (!empty($blueprint) && !$this->blueprintFactory->blueprintExists($blueprint)) {
            if ($result = $this->blueprintFactory->findByStackname($blueprint)) {
                $output->writeln('Blueprint reverse match found: <fg=green>'. $result['blueprint'] . '</>');
                $output->writeln('With ENV vars: <fg=green>' . Helper\Div::assocArrayToString($result['envvars']) .'</>');
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion("Use this blueprint and set env vars? [y/N] ", false);
                if (!$helper->ask($input, $output, $question)) {
                    throw new \Exception('Operation aborted');
                }
                $blueprint = $result['blueprint'];
                foreach ($result['envvars'] as $var => $value) {
                    $output->writeln("Setting env var: $var=$value");
                    putenv("$var=$value");
                }
            }
        }
        $input->setArgument('blueprint', $blueprint);
        return $blueprint;
    }

    protected function getStacks($nameFilter=null, $statusFilter=null)
    {
        return array_keys($this->getStackFactory()->getStacksFromApi(false, $nameFilter, $statusFilter));
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
            try {
                return parent::run($input, $output);
            } catch (CloudFormationException $exception) {
                throw Exception::refineException($exception);
            }
        } catch (StackNoUpdatesToBePerformedException $e) {
            $output->writeln('No updates are to be performed.');
            return 0; // exit code
        }
    }

}
