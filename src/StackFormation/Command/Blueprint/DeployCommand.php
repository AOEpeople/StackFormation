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
                'no-observe',
                'no',
                InputOption::VALUE_NONE,
                'Don\'t observe stack after deploying'
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
        $blueprint = $this->blueprintFactory->getBlueprint($input->getArgument('blueprint'));
        $stackName = $blueprint->getStackName();

        $dryRun = $input->getOption('dryrun');
        $deleteOnTerminate = $input->getOption('deleteOnTerminate');
        $noObserve = $input->getOption('no-observe');

        if ($deleteOnTerminate && $noObserve) {
            throw new \Exception('--deleteOnTerminate cannot be used with --no-observe');
        }

        try {

            $blueprint->deploy($dryRun, $this->stackFactory);

        } catch (CloudFormationException $exception) {

            $message = \StackFormation\Helper::extractMessage($exception);

            // TODO: we're already checking the status in deploy(). This should be handled there
            if (strpos($message, 'is in CREATE_FAILED state and can not be updated.') !== false) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Stack is in CREATE_FAILED state. Do you want to delete it first? [Y/n]');
                $confirmed = $helper->ask($input, $output, $question);
                if ($confirmed) {
                    $output->writeln('Deleting failed stack ' . $stackName);
                    $this->stackFactory->getStack($stackName)->delete()->observe($output);
                    $output->writeln('Deletion completed. Now deploying stack: ' . $stackName);
                    $blueprint->deploy($dryRun, $this->stackFactory);
                }
            } elseif (strpos($message, 'is in DELETE_IN_PROGRESS state and can not be updated.') !== false) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Stack is in DELETE_IN_PROGRESS state. Do you want to wait and deploy then? [Y/n]');
                $confirmed = $helper->ask($input, $output, $question);
                if ($confirmed) {
                    $this->stackFactory->getStack($stackName)->observe($output);
                    $output->writeln('Deletion completed. Now deploying stack: ' . $stackName);
                    $blueprint->deploy($dryRun, $this->stackFactory);
                }
            } elseif (strpos($message, 'is in UPDATE_IN_PROGRESS state and can not be updated.') !== false) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Stack is in UPDATE_IN_PROGRESS state. Do you want to cancel the current update and deploy then? [Y/n]');
                $confirmed = $helper->ask($input, $output, $question);
                if ($confirmed) {
                    $output->writeln('Cancelling update for ' . $stackName);
                    $this->stackFactory->getStack($stackName)->cancelUpdate()->observe($output);
                    $output->writeln('Cancellation completed. Now deploying stack: ' . $stackName);
                    $blueprint->deploy($dryRun, $this->stackFactory);
                }
            } else {
                throw $exception;
            }
        }

        if (!$dryRun) {
            $output->writeln("Triggered deployment of stack '$stackName'.");

            if ($noObserve) {
                $output->writeln("\n-> Run this to observe the stack creation/update:");
                $output->writeln("{$GLOBALS['argv'][0]} stack:observe $stackName\n");
            } else {
                return $this->stackFactory->getStack($stackName)->observe($output);
            }
        }
    }
}
