<?php

namespace AwsInspector\Command\Profile;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class DisableCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('profile:disable')
            ->setDescription('Disable current profile');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_file('.env')) {
            $output->writeln('No .env file found');
            return;
        }
        if (!unlink('.env')) {
            throw new \Exception('Error deleting .env file');
        }
        $output->writeln('Deleted file .env');
    }

}