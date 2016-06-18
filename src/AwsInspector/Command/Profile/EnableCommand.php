<?php

namespace AwsInspector\Command\Profile;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class EnableCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('profile:enable')
            ->setDescription('Enable profile')
            ->addArgument(
                'profile',
                InputArgument::REQUIRED,
                'Profile'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $profile = $input->getArgument('profile');
        if (empty($profile)) {

            $profileManager = new \AwsInspector\ProfileManager();

            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Please select the profile you want to use',
                $profileManager->listAllProfiles()
            );

            $question->setErrorMessage('Profile %s is invalid.');

            $profile = $helper->ask($input, $output, $question);
            $output->writeln('Selected Profile: '.$profile);

            $input->setArgument('profile', $profile);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $profileManager = new \AwsInspector\ProfileManager();
        $file = $profileManager->writeProfileToDotEnv($input->getArgument('profile'));
        $output->writeln('File written: ' . $file);
    }

}