<?php

namespace AwsInspector\Command\Ec2;

use AwsInspector\Finder;
use AwsInspector\Model\Ec2\Instance;
use AwsInspector\Model\Ec2\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('ec2:list')
            ->setDescription('List all instances')
            ->addOption(
                'tag',
                't',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'tag (Example: "Environment:Deploy")'
            )
            ->addOption(
                'column',
                'c',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Extra column (tag)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tags = $input->getOption('tag');
        $tags = $this->convertTags($tags);

        $mapping = [
            'InstanceId' => 'InstanceId',
            'ImageId' => 'ImageId',
            'State' => 'State.Name',
            'SubnetId' => 'SubnetId',
            'AZ' => 'Placement.AvailabilityZone',
            'PublicIp' => 'PublicIpAddress',
            'PrivateIp' => 'PrivateIpAddress',
            'KeyName' => 'KeyName'
        ];

        // dynamically add current tags
        foreach (array_keys($tags) as $tagName) {
            $mapping[$tagName] = 'Tags[?Key==`'.$tagName.'`].Value | [0]';
        }

        foreach ($input->getOption('column') as $tagName) {
            $mapping[$tagName] = 'Tags[?Key==`'.$tagName.'`].Value | [0]';
        }

        $repository = new Repository();
        $instanceCollection = $repository->findEc2InstancesByTags($tags);

        $rows = [];
        foreach ($instanceCollection as $instance) { /* @var $instance Instance */
            $rows[] = $instance->extractData($mapping);
        }

        if (count($rows)) {
            $table = new \Symfony\Component\Console\Helper\Table($output);
            $table
                ->setHeaders(array_keys(end($rows)))
                ->setRows($rows);
            $table->render();
        } else {
            $output->writeln('No matching instances found.');
        }
    }

}