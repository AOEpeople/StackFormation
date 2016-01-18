<?php

namespace StackFormation\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShowLiveCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:show-live')
            ->setDescription('Show parameters, resources and outputs from live stacks')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack'
            )
            ->addArgument(
                'section',
                InputArgument::OPTIONAL,
                'Section',
                null
            )
            ->addArgument(
                'key',
                InputArgument::OPTIONAL,
                'Key',
                null
            )
            ->addOption(
                'json',
                null,
                InputOption::VALUE_NONE,
                'JSON Output',
                null
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForLiveStack($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $asJSON = $input->getOption('json');

        $stack = $input->getArgument('stack');

        $sections = ['tags', 'parameters', 'resources', 'outputs'];
        $section = array_filter(array_map('trim', explode(',', $input->getArgument('section'))));
        if ($section) {
            $sections = array_intersect($sections, $section);
        }

        $key = array_filter(array_map('trim', explode(',', $input->getArgument('key'))));

        $outputData = [];

        foreach ($sections as $section) {
            $data = $this->stackManager->{'get' . ucfirst($section)}($stack);
            if ($key) {
                $data = array_intersect_key($data, array_flip($key));
            }
            $outputData[$section] = $data;
        }

        if ($asJSON) {
            $output->writeln(json_encode($outputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT));
        } else {
            $output->writeln("Stack '$stack':");

            foreach ($outputData as $section => $sectionData) {
                $rows = [];
                foreach ($sectionData as $k => $v) {
                    $v = strlen($v) > 100 ? substr($v, 0, 100) . "..." : $v;
                    $rows[] = [$k, $v];
                }
                $output->writeln('');
                $output->writeln("=== " . strtoupper($section) . " ===");
                $table = new Table($output);
                $table->setHeaders(['Key', 'Value'])
                    ->setRows($rows)
                    ->render();
            }
        }
    }
}
