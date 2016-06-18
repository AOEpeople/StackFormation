<?php

namespace AwsInspector\Command\Ec2;

use AwsInspector\Model\Ec2\Instance;
use AwsInspector\Model\Ec2\Repository;
use AwsInspector\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class SshCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('ec2:ssh')
            ->setDescription('SSH into an EC2 instance')
            ->addArgument(
                'instance',
                InputArgument::REQUIRED,
                'Instance (public or private IP address or instance id)'
            )
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
            )
            ->addOption(
                'print',
                null,
                InputOption::VALUE_NONE,
                'Print ssh command instead of connecting'
            )
            ->addOption(
                'command',
                null,
                InputOption::VALUE_OPTIONAL,
                'Command'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $instance = $input->getArgument('instance');
        if (empty($instance)) {
            // find instances based on tag(s)
            $tags = $input->getOption('tag');
            $tags = $this->convertTags($tags);

            $repository = new Repository();
            $instanceCollection = $repository->findEc2InstancesByTags($tags);

            $count = count($instanceCollection);
            if ($count == 0) {
                throw new \Exception('No instance found matching the given tags');
            } elseif ($count == 1) {
                $instanceObj = $instanceCollection->getFirst(); /* @var $instanceObj Instance */
                $input->setArgument('instance', $instanceObj->getInstanceId());
            } else {

                $mapping=[];
                // dynamically add current tags
                foreach (array_keys($tags) as $tagName) {
                    $mapping[$tagName] = 'Tags[?Key==`'.$tagName.'`].Value | [0]';
                }
                foreach ($input->getOption('column') as $tagName) {
                    $mapping[$tagName] = 'Tags[?Key==`'.$tagName.'`].Value | [0]';
                }

                $labels = [];
                foreach($instanceCollection as $instanceObj) { /* @var $instanceObj Instance */
                    $instanceLabel = $instanceObj->getInstanceId();
                    $tmp = [];
                    foreach ($instanceObj->extractData($mapping) as $field => $value) {
                        if (!empty($value)) {
                            $tmp[] = "$field: $value";
                        }
                    }
                    if (count($tmp)) {
                        $labels[] = $instanceLabel . ' (' . implode('; ', $tmp). ')';
                    } else {
                        $labels[] = $instanceLabel;
                    }
                }

                $helper = $this->getHelper('question');
                $question = new ChoiceQuestion('Please select an instance', $labels);

                $question->setErrorMessage('Instance %s is invalid.');

                $instance = $helper->ask($input, $output, $question);
                $output->writeln('Selected Instance: ' . $instance);

                list($instance) = explode(' ', $instance);
                $input->setArgument('instance', $instance);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Registry::set('output', $output);

        $instance = $input->getArgument('instance');

        $repository = new Repository();
        $instance = $repository->findEc2Instance($instance);
        if (!$instance instanceof Instance) {
            throw new \Exception('Could not find instance');
        }

        $output->writeln('[Found instance: ' . $instance->getDefaultUsername() . '@' . $instance->getConnectionIp() . ']');

        $connection = $instance->getSshConnection();

        if ($command = $input->getOption('command')) {
            $commandObj = new \AwsInspector\Ssh\Command($connection, $command);
            if ($input->getOption('print')) {
                $output->writeln($commandObj->__toString());
                return 0;
            }
            $res = $commandObj->exec();
            $output->writeln($res['output']);
            return $res['returnVar'];
        }

        if ($input->getOption('print')) {
            $output->writeln($connection->__toString());
            return 0;
        }

        $connection->connect();
        return 0;
    }

}