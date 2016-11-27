<?php

namespace StackFormation\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class SetupCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('setup')
            ->setDescription('Setup stackformation');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $data = [];
        
        $awsAccessKeyIdQuestion= new Question('Please enter your AWS_ACCESS_KEY_ID: ');
        $data['AWS_ACCESS_KEY_ID'] = $helper->ask($input, $output, $awsAccessKeyIdQuestion);
        
        $awsSecretAccessKeyIdQuestion= new Question('Please enter your AWS_SECRET_ACCESS_KEY: ');
        $data['AWS_SECRET_ACCESS_KEY'] = $helper->ask($input, $output, $awsSecretAccessKeyIdQuestion);
        
        $regionQuestion = new Question('Please enter the name of your region [eu-west-1]: ', 'eu-west-1');
        $data['AWS_DEFAULT_REGION'] = $helper->ask($input, $output, $regionQuestion);
        
        try {
            $fs = new Filesystem();
            $fs->dumpFile('.env.default', $this->parseContent($data));
            $output->writeln('.env.default file was successfully created');
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while creating .env.default file at ".$e->getPath();
        }
    }
    
    /**
     * @param array $$data
     * @return string
     */
    protected function parseContent(array $data)
    {
        $content = '';
        foreach ($data as $key => $value) {
            $content .= $key . '=' . $value . PHP_EOL;
        }
        
        return $content;
    }
}
