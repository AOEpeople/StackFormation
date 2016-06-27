<?php

namespace StackFormation\Command\Blueprint;

use Aws\CloudFormation\Exception\CloudFormationException;
use StackFormation\BlueprintAction;
use StackFormation\Exception\StackCannotBeUpdatedException;
use StackFormation\Exception\StackNotFoundException;
use StackFormation\Exception\StackNoUpdatesToBePerformedException;
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

        $blueprint = $this->blueprintFactory->getBlueprint($input->getArgument('blueprint'));
        $blueprintAction = new BlueprintAction($blueprint, $this->profileManager, $output);

        $stackFactory = $this->profileManager->getStackFactory($blueprint->getProfile());

        try {
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
                    try {
                        $changeSetResult = $blueprintAction->getChangeSet();
                        $table = new ChangeSetTable($output);
                        $table->render($changeSetResult);

                        $helper = $this->getHelper('question');
                        $question = new ConfirmationQuestion("Do you want to proceed? [y/N] ", false);
                        if (!$helper->ask($input, $output, $question)) {
                            throw new \Exception('Operation aborted');
                        }
                    } catch (StackNotFoundException $e) {
                        $helper = $this->getHelper('question');
                        $question = new ConfirmationQuestion("This stack does not exist yet. Do you want to proceed creating it? [y/N] ", false);
                        if (!$helper->ask($input, $output, $question)) {
                            throw new \Exception('Operation aborted');
                        }
                    }
                }

                $blueprintAction = new BlueprintAction($blueprint, $this->profileManager, $output);
                $blueprintAction->deploy($dryRun);

            } catch (CloudFormationException $exception) {
                throw \StackFormation\Helper::refineException($exception);
            }
        } catch (StackNoUpdatesToBePerformedException $e) {
            $output->writeln('No updates are to be performed.');
            return 0; // exit code
        } catch (StackCannotBeUpdatedException $e) {
            $helper = $this->getHelper('question');
            switch ($e->getState()) {
                case 'CREATE_FAILED':
                    $question = new ConfirmationQuestion('Stack is in CREATE_FAILED state. Do you want to delete it first? [Y/n]');
                    $confirmed = $helper->ask($input, $output, $question);
                    if ($confirmed) {
                        $output->writeln('Deleting failed stack ' . $stackName);
                        $this->stackFactory->getStack($stackName)->delete()->observe($output, $this->stackFactory);
                        $output->writeln('Deletion completed. Now deploying stack: ' . $stackName);
                        $blueprintAction->deploy($dryRun);
                    }
                    break;
                case 'DELETE_IN_PROGRESS':
                    $question = new ConfirmationQuestion('Stack is in DELETE_IN_PROGRESS state. Do you want to wait and deploy then? [Y/n]');
                    $confirmed = $helper->ask($input, $output, $question);
                    if ($confirmed) {
                        $this->stackFactory->getStack($stackName)->observe($output, $this->stackFactory);
                        $output->writeln('Deletion completed. Now deploying stack: ' . $stackName);
                        $blueprintAction->deploy($dryRun);
                    }
                    break;
                case 'UPDATE_IN_PROGRESS':
                    $question = new ConfirmationQuestion('Stack is in UPDATE_IN_PROGRESS state. Do you want to cancel the current update and deploy then? [Y/n]');
                    $confirmed = $helper->ask($input, $output, $question);
                    if ($confirmed) {
                        $output->writeln('Cancelling update for ' . $stackName);
                        $this->stackFactory->getStack($stackName)->cancelUpdate()->observe($output, $this->stackFactory);
                        $output->writeln('Cancellation completed. Now deploying stack: ' . $stackName);
                        $blueprintAction->deploy($dryRun);
                    }
                break;
                default: throw $e;
            }
        }

        if (!$dryRun) {
            $output->writeln("Triggered deployment of stack '$stackName'.");

            if ($noObserve) {
                $output->writeln("\n-> Run this to observe the stack creation/update:");
                $output->writeln("{$GLOBALS['argv'][0]} stack:observe $stackName\n");
            } else {
                $stackFactory = $this->profileManager->getStackFactory($blueprint->getProfile());
                return $stackFactory->getStack($stackName, true)->observe($output, $this->stackFactory, $deleteOnTerminate);
            }
        }
    }
}
