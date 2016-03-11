<?php

namespace StackFormation\Command;

use Aws\CloudFormation\Exception\CloudFormationException;
use StackFormation\Helper;
use StackFormation\StackManager;
use Symfony\Component\Console\Helper\FormatterHelper;
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

    protected function interactAskForBlueprint(InputInterface $input, OutputInterface $output)
    {
        $blueprint = $input->getArgument('blueprint');
        if (empty($blueprint)) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Please select a blueprint', $this->stackManager->getConfig()->getStackLabels());

            $question->setErrorMessage('Blueprint %s is invalid.');

            $blueprint = $helper->ask($input, $output, $question);
            $output->writeln('Selected blueprint: ' . $blueprint);

            list($stackName) = explode(' ', $blueprint);
            $input->setArgument('blueprint', $stackName);
        }

        return $blueprint;
    }

    protected function getRemoteStacks($nameFilter=null, $statusFilter=null)
    {
        return array_keys($this->stackManager->getStacksFromApi(false, $nameFilter, $statusFilter));
    }

    public function interactAskForLiveStack(InputInterface $input, OutputInterface $output, $nameFilter=null, $statusFilter=null)
    {
        $stack = $input->getArgument('stack');
        if (empty($stack)) {
            $choices = $this->getRemoteStacks($nameFilter, $statusFilter);

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

    protected function extractMessage(CloudFormationException $exception)
    {
        $message = (string)$exception->getResponse()->getBody();
        $xml = simplexml_load_string($message);
        if ($xml !== false && $xml->Error->Message) {
            return $xml->Error->Message;
        }

        return $exception->getMessage();
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        try {
            return parent::run($input, $output);
        } catch (CloudFormationException $exception) {
            $message = $this->extractMessage($exception);
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

    protected function arrayToString(array $a)
    {
        ksort($a);
        $lines = [];
        foreach ($a as $key => $value) {
            $lines[] = "$key: $value";
        }
        return implode("\n", $lines);
    }

    protected function printDiff($stringA, $stringB)
    {
        if ($stringA === $stringB) {
            return 0; // that's what diff would return
        }

        $fileA = tempnam(sys_get_temp_dir(), 'sfn_a_');
        file_put_contents($fileA, $stringA);

        $fileB = tempnam(sys_get_temp_dir(), 'sfn_b_');
        file_put_contents($fileB, $stringB);

        $command = is_file('/usr/bin/colordiff') ? 'colordiff' : 'diff';
        $command .= " -u $fileA $fileB";

        passthru($command, $returnVar);

        unlink($fileA);
        unlink($fileB);
        return $returnVar;
    }

    protected function normalizeJson($json)
    {
        return json_encode(json_decode($json, true), JSON_PRETTY_PRINT);
    }
}
