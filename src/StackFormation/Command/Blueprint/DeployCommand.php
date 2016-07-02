<?php

namespace StackFormation\Command\Blueprint;

use Aws\CloudFormation\Exception\CloudFormationException;
use StackFormation\Blueprint;
use StackFormation\BlueprintAction;
use StackFormation\Exception\OperationAbortedException;
use StackFormation\Exception\StackCannotBeUpdatedException;
use StackFormation\Exception\StackNotFoundException;
use StackFormation\Exception\StackNoUpdatesToBePerformedException;
use StackFormation\Helper\ChangeSetTable;
use StackFormation\Helper\Exception;
use StackFormation\Observer;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeployCommand extends \StackFormation\Command\Blueprint\AbstractBlueprintCommand
{

    protected function configure()
    {
        $this
            ->setName('blueprint:deploy')
            ->setDescription('Deploy blueprint')
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

    protected function executeWithBlueprint(Blueprint $blueprint, InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dryrun');
        if ($dryRun) {
            $formatter = new \Symfony\Component\Console\Helper\FormatterHelper();
            $formattedBlock = $formatter->formatBlock(['Dry Run!'], 'error', true);
            $output->writeln("\n$formattedBlock\n");
        }

        if ($input->getOption('observe')) {
            $output->writeln('-o/--observe is deprecated now. Deployments are being observed by default. Please remove this option.');
        }

        $stackName = $blueprint->getStackName();

        $dryRun = $input->getOption('dryrun');
        $deleteOnTerminate = $input->getOption('deleteOnTerminate');
        $noObserve = $input->getOption('no-observe');

        if ($deleteOnTerminate && $noObserve) {
            throw new \InvalidArgumentException('--deleteOnTerminate cannot be used with --no-observe');
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

                    $questionHelper = $this->getHelper('question');
                    $question = new ConfirmationQuestion("Do you want to proceed? [y/N] ", false);
                    if (!$questionHelper->ask($input, $output, $question)) {
                        throw new OperationAbortedException('blueprint:deploy', 'review-parameters');
                    }
                }
                if ($input->getOption('review-changeset')) {
                    $output->writeln("\n\n== Review change set: ==");
                    try {
                        $changeSetResult = $blueprintAction->getChangeSet();
                        $table = new ChangeSetTable($output);
                        $table->render($changeSetResult);

                        $questionHelper = $this->getHelper('question');
                        $question = new ConfirmationQuestion("Do you want to proceed? [y/N] ", false);
                        if (!$questionHelper->ask($input, $output, $question)) {
                            throw new OperationAbortedException('blueprint:deploy', 'review-changeset');
                        }
                    } catch (StackNotFoundException $e) {
                        $questionHelper = $this->getHelper('question');
                        $question = new ConfirmationQuestion("This stack does not exist yet. Do you want to proceed creating it? [y/N] ", false);
                        if (!$questionHelper->ask($input, $output, $question)) {
                            throw new OperationAbortedException('blueprint:deploy', 'Stack does not exist');
                        }
                    }
                }

                $blueprintAction = new BlueprintAction($blueprint, $this->profileManager, $output);
                $blueprintAction->deploy($dryRun);
                $output->writeln("Triggered deployment of stack '$stackName'.");

            } catch (CloudFormationException $exception) {
                throw Exception::refineException($exception);
            }
        } catch (StackNoUpdatesToBePerformedException $e) {
            $output->writeln('No updates are to be performed.');
            return 0; // exit code
        } catch (StackCannotBeUpdatedException $e) {
            $questionHelper = $this->getHelper('question'); /* @var $questionHelper QuestionHelper */
            $stack = $stackFactory->getStack($stackName, true);
            switch ($e->getState()) {
                case 'CREATE_FAILED':
                    if ($questionHelper->ask($input, $output, new ConfirmationQuestion('Stack is in CREATE_FAILED state. Do you want to delete it first? [Y/n]'))) {
                        $output->writeln('Deleting failed stack ' . $stackName);
                        $stack->delete();
                        $observer = new Observer($stack, $stackFactory, $output);
                        if ($deleteOnTerminate) { $observer->deleteOnSignal(); }
                        $observer->observeStackActivity();
                        $output->writeln('Deletion completed. Now deploying stack: ' . $stackName);
                        $blueprintAction->deploy($dryRun);
                    }
                    break;
                case 'DELETE_IN_PROGRESS':
                    if ($questionHelper->ask($input, $output, new ConfirmationQuestion('Stack is in DELETE_IN_PROGRESS state. Do you want to wait and deploy then? [Y/n]'))) {
                        $output->writeln('Waiting until deletion completes for ' . $stackName);
                        $observer = new Observer($stack, $stackFactory, $output);
                        if ($deleteOnTerminate) { $observer->deleteOnSignal(); }
                        $observer->observeStackActivity();
                        $output->writeln('Deletion completed. Now deploying stack: ' . $stackName);
                        $blueprintAction->deploy($dryRun);
                    }
                    break;
                case 'UPDATE_IN_PROGRESS':
                    if ($questionHelper->ask($input, $output, new ConfirmationQuestion('Stack is in UPDATE_IN_PROGRESS state. Do you want to cancel the current update and deploy then? [Y/n]'))) {
                        $output->writeln('Cancelling update for ' . $stackName);
                        $stack->cancelUpdate();
                        $observer = new Observer($stack, $stackFactory, $output);
                        if ($deleteOnTerminate) { $observer->deleteOnSignal(); }
                        $observer->observeStackActivity();
                        $output->writeln('Cancellation completed. Now deploying stack: ' . $stackName);
                        $blueprintAction->deploy($dryRun);
                    }
                break;
                default: throw $e;
            }
        }

        if (!$dryRun) {
            if ($noObserve) {
                $output->writeln("\n-> Run this to observe the stack creation/update:");
                $output->writeln("{$GLOBALS['argv'][0]} stack:observe $stackName\n");
            } else {
                $stack = $stackFactory->getStack($stackName, true);
                $observer = new Observer($stack, $stackFactory, $output);
                if ($deleteOnTerminate) { $observer->deleteOnSignal(); }
                return $observer->observeStackActivity();
            }
        }
    }
}
