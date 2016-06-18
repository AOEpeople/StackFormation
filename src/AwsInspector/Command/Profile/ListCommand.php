<?php

namespace AwsInspector\Command\Profile;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('profile:list')
            ->setDescription('List all AWS profiles found in configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $profileManager = new \AwsInspector\ProfileManager();

        $rows=[];
        foreach($profileManager->listAllProfiles() as $profileName) {
            $rows[] = [$profileName];
        }

        $table = new \Symfony\Component\Console\Helper\Table($output);
        $table
            ->setHeaders(array('Profile Name'))
            ->setRows($rows)
        ;
        $table->render();
    }

}