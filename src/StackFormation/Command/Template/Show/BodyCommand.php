<?php

namespace StackFormation\Command\Template\Show;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BodyCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('template:show:body')
            ->setDescription('Preview preprocessed template body')
            ->addArgument(
                'template',
                InputArgument::REQUIRED,
                'Template'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForTemplate($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $template = $input->getArgument('template');
        $output->writeln($this->stackManager->getPreprocessedTemplate($template));
    }
}
