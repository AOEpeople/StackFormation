<?php

namespace StackFormation\Command\Blueprint;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('blueprint:migrate')
            ->setDescription('Migrate old stacks.yml files to blueprint.yml files');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (is_dir('stacks')) {
            $output->writeln("Found 'stacks' directory. Renaming it to 'blueprints'");
            $res = rename('stacks', 'blueprints');
            if ($res === false) {
                throw new \Exception('Error while renaming directory stacks to blueprints');
            }
        }

        $output->writeln('Finding stacks.yml files');
        foreach (\StackFormation\Config::findAllConfigurationFiles('blueprints', 'stacks.yml') as $stacksYml) {
            $output->writeln('Found: '.$stacksYml);
            $fileContent = file_get_contents($stacksYml);
            $fileContent = preg_replace('/^stacks:/', 'blueprints:', $fileContent);
            $dirname = dirname($stacksYml);
            $res = file_put_contents($dirname.'/blueprints.yml', $fileContent);
            if ($res === false) {
                throw new \Exception('Error while writing: ' . $dirname.'/blueprints.yml');
            }
            $res = unlink($stacksYml);
            if ($res === false) {
                throw new \Exception('Error deleting: ' . $stacksYml);
            }
            $output->writeln("-> Renaming to: '$dirname/blueprints.yml' and replacing 'stacks:' with 'blueprints:'");
        }
    }

}

