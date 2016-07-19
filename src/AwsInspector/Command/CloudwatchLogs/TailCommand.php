<?php

namespace AwsInspector\Command\CloudwatchLogs;

use AwsInspector\Model\CloudWatchLogs\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class TailCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('cloudwatchlogs:tail')
            ->setDescription('Show Log Groups')
            ->addArgument(
                'group',
                InputArgument::REQUIRED,
                'Log group name'
            )
            ->addArgument(
                'stream',
                InputArgument::REQUIRED,
                'Log stream name'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $logGroupName = $input->getArgument('group') ?: '*';

        $repository = new Repository();
        $groups = $repository->findLogGroups($logGroupName);
        if (count($groups) == 0) {
            throw new \InvalidArgumentException('Could not find any matching log groups');
        } elseif (count($groups) == 1) {
            $logGroupName = $groups->getFirst()->getLogGroupName();
        } else {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Please select a log group', $groups->flatten('getLogGroupName'));
            $question->setErrorMessage('Log group %s is invalid.');
            $logGroupName = $helper->ask($input, $output, $question);
            $output->writeln('Selected log group: ' . $logGroupName);
        }
        $input->setArgument('group', $logGroupName);

        $logStreamName = $input->getArgument('stream') ?: '*';
        if ($logStreamName == '__FIRST__') {
            $streams = $repository->findLogStreams($logGroupName);
            $logStreamName = $streams->getFirst()->getLogStreamName();
        } else {
            $streams = $repository->findLogStreams($logGroupName, $logStreamName);
            if (count($streams) == 0) {
                throw new \InvalidArgumentException('Could not find any matching log streams');
            } elseif (count($streams) == 1) {
                $logStreamName = $streams->getFirst()->getLogStreamName();
            } else {
                $helper = $this->getHelper('question');
                $question = new ChoiceQuestion('Please select a log stream', $streams->flatten('getLogStreamName'));
                $question->setErrorMessage('Log stream %s is invalid.');
                $logStreamName = $helper->ask($input, $output, $question);
                $output->writeln('Selected log stream: ' . $logStreamName);
            }
        }
        $input->setArgument('stream', $logStreamName);

    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logGroupName = $input->getArgument('group');
        $logStream = $input->getArgument('stream');
        $repository = new Repository();
        $nextForwardToken = null;
        do {
            $output->writeln('<fg=yellow>(Polling...)</>');
            $events = $repository->findLogEvents($logGroupName, $logStream, $nextForwardToken);
            $output->write(implode('', $this->decorateLog($events)));
            sleep(10);
        } while (true);
    }

    public function decorateLog(array $lines)
    {
        array_walk($lines, function(&$line) {
            $line = preg_replace('/^(\d{4})-(\d{2})-(\d{2})T.*[a-f0-9-]{36}\s+/', '... ', $line);
            $line = str_replace('START RequestId:', "\n".'<fg=green>START RequestId:</>', $line);
            $line = str_replace('END RequestId:', '<fg=green>END RequestId:</>', $line);
            $line = str_replace('REPORT RequestId:', '<fg=green>REPORT RequestId:</>', $line);
        });
        return $lines;
    }

}
