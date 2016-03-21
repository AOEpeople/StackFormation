<?php

namespace StackFormation\Command\Blueprint;

use Aws\CloudFormation\Exception\CloudFormationException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeployCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('blueprint:deploy')
            ->setDescription('Deploy blueprint')
            ->addArgument(
                'blueprint',
                InputArgument::REQUIRED,
                'Blueprint'
            )
            ->addOption(
                'observe',
                'o',
                InputOption::VALUE_NONE,
                'Observe stack after'
            )
            ->addOption(
                'deleteOnTerminate',
                null,
                InputOption::VALUE_NONE,
                'Delete current stack if StackFormation received SIGTERM (e.g. Jenkins job abort) or SIGINT (e.g. CTRL+C)'
            )
            ->addOption(
                'dryrun',
                'd',
                InputOption::VALUE_NONE,
                'Dry run'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dryrun');
        if ($dryRun) {
            $formatter = new \Symfony\Component\Console\Helper\FormatterHelper();
            $formattedBlock = $formatter->formatBlock(['Dry Run!'], 'error', true);
            $output->writeln("\n$formattedBlock\n");
        }
        $this->interactAskForBlueprint($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $blueprint = $input->getArgument('blueprint');
        $dryRun = $input->getOption('dryrun');
        $deleteOnTerminate = $input->getOption('deleteOnTerminate');
        $observe = $input->getOption('observe');

        if ($deleteOnTerminate && !$observe) {
            throw new \Exception('--deleteOnTerminate can only be used with --observe');
        }

        try {
            $this->stackManager->deployStack($blueprint, $dryRun);
        } catch (CloudFormationException $exception) {
            $message = \StackFormation\Helper::extractMessage($exception);
            if (strpos($message, 'is in CREATE_FAILED state and can not be updated.') !== false) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Stack is in CREATE_FAILED state. Do you want to delete it first? [Y/n]');
                $confirmed = $helper->ask($input, $output, $question);
                if ($confirmed) {
                    $effectiveStackName = $this->stackManager->getConfig()->getEffectiveStackName($blueprint);
                    $output->writeln('Deleting failed stack ' . $effectiveStackName);
                    $this->stackManager->deleteStack($effectiveStackName);
                    $this->stackManager->observeStackActivity($effectiveStackName, $output, 10);

                    $output->writeln('Deletion completed. Now deploying stack: ' . $effectiveStackName);
                    $this->stackManager->deployStack($blueprint, $dryRun);
                }
            } else {
                throw $exception;
            }
        }

        if (!$dryRun) {
            $effectiveStackName = $this->stackManager->getConfig()->getEffectiveStackName($blueprint);
            $output->writeln("Triggered deployment of stack '$effectiveStackName'.");

            if ($observe) {
                return $this->stackManager->observeStackActivity($effectiveStackName, $output, 10, $deleteOnTerminate);
            } else {
                $output->writeln("\n-> Run this to observe the stack creation/update:");
                $output->writeln("{$GLOBALS['argv'][0]} stack:observe $effectiveStackName\n");
            }
        }
    }
}
