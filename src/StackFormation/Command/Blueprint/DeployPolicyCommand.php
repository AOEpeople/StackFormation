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
use StackFormation\Stack;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeployPolicyCommand extends \StackFormation\Command\Blueprint\AbstractBlueprintCommand
{

    protected function configure()
    {
        $this
            ->setName('blueprint:deploy-policy')
            ->setDescription('Deploy stack policy');
    }

    protected function executeWithBlueprint(Blueprint $blueprint, InputInterface $input, OutputInterface $output)
    {
        $blueprintAction = new BlueprintAction($blueprint, $this->profileManager, $output);
        $blueprintAction->updateStackPolicy();
        $output->writeln('Updated stack policy.');
    }
}
