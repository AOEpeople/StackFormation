<?php

namespace StackFormation\Command;

use StackFormation\Helper;
use StackFormation\StackManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\ChoiceQuestion;

abstract class AbstractCommand extends Command
{

    /**
     * @var StackManager
     */
    protected $stackManager;

    public function __construct($name = null)
    {
        $this->stackManager = new StackManager();

        parent::__construct($name);
    }


    protected function interact_askForConfigStack(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');
        if (empty($stack)) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Please select a stack', $this->stackManager->getConfig()->getStackLabels());

            $question->setErrorMessage('Stack %s is invalid.');

            $stack = $helper->ask($input, $output, $question);
            $output->writeln('Selected Stack: '.$stack);

            list($stackName) = explode(' ', $stack);
            $input->setArgument('stack', $stackName);
        }
        return $stack;
    }

    public function interact_askForLiveStack(InputInterface $input, OutputInterface $output, $multiple=false, $resolveWildcard=false) {
        $stack = $input->getArgument('stack');
        $choices = null;
        if (empty($stack)) {
            $choices = array_keys($this->stackManager->getStacksFromApi());

            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Please select a stack', $choices);

            $question->setErrorMessage('Stack %s is invalid.');

            $stack = $helper->ask($input, $output, $question);
            $output->writeln('Selected Stack: '.$stack);

            if ($multiple) {
                $input->setArgument('stack', [$stack]);
            } else {
                $input->setArgument('stack', $stack);
            }
        }

        if ($multiple && $resolveWildcard) {
            if (is_null($choices)) {
                $choices = array_keys($this->stackManager->getStacksFromApi());
            }
            $helper = new Helper();
            $resolvedStacks = [];
            $stacks = $input->getArgument('stack');
            foreach ($stacks as $stack) {
                $resolvedStacks = array_merge($resolvedStacks, $helper->find($stack, $choices));
            }
            $resolvedStacks = array_unique($resolvedStacks);
            $input->setArgument('stack', $resolvedStacks);
        }

        if ($multiple) {
            $except = $input->getOption('except');
            if (!empty($except)) {
                $stacks = $input->getArgument('stack');
                if (($key = array_search($except, $stacks)) !== false) {
                    $output->writeln('Excluding stack: ' . $stacks[$key]);
                    unset($stacks[$key]);
                }
                $input->setArgument('stack', $stacks);
            }
        }

        return $stack;
    }

    protected function extractMessage(\Aws\CloudFormation\Exception\CloudFormationException $exception) {
        $message = (string)$exception->getResponse()->getBody();
        $xml = simplexml_load_string($message);
        if ($xml !== false && $xml->Error->Message) {
            return $xml->Error->Message;
        }
        return $exception->getMessage();
    }

    public function run(InputInterface $input, OutputInterface $output) {
        try {
            return parent::run($input, $output);
        } catch (\Aws\CloudFormation\Exception\CloudFormationException $exception) {
            $message = $this->extractMessage($exception);
            if (strpos($message, 'No updates are to be performed.') !== false) {
                $output->writeln('No updates are to be performed.');
            } else {
                $formatter = new \Symfony\Component\Console\Helper\FormatterHelper();
                $formattedBlock = $formatter->formatBlock(['[CloudFormationException]', '', $message], 'error', true);
                $output->writeln("\n\n$formattedBlock\n\n");
                return 1; // exit code
            }
        }
    }

}
