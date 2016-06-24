<?php

namespace StackFormation\Command\Blueprint;

use Aws\CloudFormation\Exception\CloudFormationException;
use StackFormation\BlueprintAction;
use StackFormation\Helper\ChangeSetTable;
use Symfony\Component\Console\Helper\Table;
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
                's',
                InputOption::VALUE_NONE,
                'Don\'t observe stack after deploying'
            )
            ->addOption(
                'observe',
                'o',
                InputOption::VALUE_NONE,
                'Deprecated. Deployments are being observed by default now'
            )
            ->addOption(
                'review-parameters',
                'p',
                InputOption::VALUE_NONE,
                'Review parameters before deploying'
            )
            ->addOption(
                'review-changeset',
                'c',
                InputOption::VALUE_NONE,
                'Review changeset before deploying'
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
        if ($input->getOption('observe')) {
            $output->writeln('-o/--observe is deprecated now. Deployments are being observed by default. Please remove this option.');
        }

        $blueprint = $this->blueprintFactory->getBlueprint($input->getArgument('blueprint'));
        $stackName = $blueprint->getStackName();

        $dryRun = $input->getOption('dryrun');
        $deleteOnTerminate = $input->getOption('deleteOnTerminate');
        $noObserve = $input->getOption('no-observe');

        if ($deleteOnTerminate && $noObserve) {
            throw new \Exception('--deleteOnTerminate cannot be used with --no-observe');
        }

        try {

            if ($input->getOption('review-parameters')) {
                $output->writeln("\n\n== Review parameters: ==");
                $table = new Table($output);
                $table->setHeaders(['Key', 'Value'])->setRows($blueprint->getParameters());
                $table->render();

                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion("Do you want to proceed? [y/N] ", false);
                if (!$helper->ask($input, $output, $question)) {
                    throw new \Exception('Operation aborted');
                }
            }
            if ($input->getOption('review-changeset')) {
                $output->writeln("\n\n== Review change set: ==");
                $blueprint = $this->blueprintFactory->getBlueprint($input->getArgument('blueprint'));
                $blueprintAction = new BlueprintAction($blueprint, $this->profileManager, $this->stackFactory, $output);
                $changeSetResult = $blueprintAction->getChangeSet();
                $table = new ChangeSetTable($output);
                $table->render($changeSetResult);

                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion("Do you want to proceed? [y/N] ", false);
                if (!$helper->ask($input, $output, $question)) {
                    throw new \Exception('Operation aborted');
                }
            }

            $blueprintAction = new BlueprintAction($blueprint, $this->profileManager, $this->stackFactory, $output);
            $blueprintAction->deploy($dryRun);

        } catch (CloudFormationException $exception) {

            $message = \StackFormation\Helper::extractMessage($exception);

            if (strpos($message, 'No updates are to be performed.') !== false) {
                $output->writeln('No updates are to be performed.');
                return 0; // exit code
            }

            // TODO: we're already checking the status in deploy(). This should be handled there
            if (strpos($message, 'is in CREATE_FAILED state and can not be updated.') !== false) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Stack is in CREATE_FAILED state. Do you want to delete it first? [Y/n]');
                $confirmed = $helper->ask($input, $output, $question);
                if ($confirmed) {
                    $output->writeln('Deleting failed stack ' . $stackName);
                    $this->stackFactory->getStack($stackName)->delete()->observe($output, $this->stackFactory);
                    $output->writeln('Deletion completed. Now deploying stack: ' . $stackName);
                    $blueprintAction->deploy($dryRun);
                }
            } elseif (strpos($message, 'is in DELETE_IN_PROGRESS state and can not be updated.') !== false) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Stack is in DELETE_IN_PROGRESS state. Do you want to wait and deploy then? [Y/n]');
                $confirmed = $helper->ask($input, $output, $question);
                if ($confirmed) {
                    $this->stackFactory->getStack($stackName)->observe($output, $this->stackFactory);
                    $output->writeln('Deletion completed. Now deploying stack: ' . $stackName);
                    $blueprintAction->deploy($dryRun);
                }
            } elseif (strpos($message, 'is in UPDATE_IN_PROGRESS state and can not be updated.') !== false) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Stack is in UPDATE_IN_PROGRESS state. Do you want to cancel the current update and deploy then? [Y/n]');
                $confirmed = $helper->ask($input, $output, $question);
                if ($confirmed) {
                    $output->writeln('Cancelling update for ' . $stackName);
                    $this->stackFactory->getStack($stackName)->cancelUpdate()->observe($output, $this->stackFactory);
                    $output->writeln('Cancellation completed. Now deploying stack: ' . $stackName);
                    $blueprintAction->deploy($dryRun);
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
                return $this->stackFactory->getStack($stackName, true)->observe($output, $this->stackFactory, $deleteOnTerminate);
            }
        }
    }
}
