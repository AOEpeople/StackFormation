<?php

namespace AwsInspector\Command\CloudwatchLogs;

use AwsInspector\Finder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShowLogGroupsCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('cloudwatchlogs:show-log-groups')
            ->setDescription('Show Log Groups')
            ->addArgument(
                'group',
                InputArgument::OPTIONAL,
                'Log group name pattern'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $groupPattern = $input->getArgument('group');
        if (empty($groupPattern)) {
            $groupPattern = '.*';
        }

        $cloudwatchLogsClient = \AwsInspector\SdkFactory::getClient('cloudwatchlogs'); /* @var $cloudwatchLogsClient \Aws\CloudWatchLogs\CloudWatchLogsClient */


        $table = new Table($output);
        $table->setHeaders(['Name', 'Retention [days]', 'Size [MB]']);

        $totalBytes = 0;
        $nextToken = null;
        do {
            $params = ['limit' => 50];
            if ($nextToken) {
                $params['nextToken'] = $nextToken;
            }
            $result = $cloudwatchLogsClient->describeLogGroups($params);
            foreach ($result->get('logGroups') as $logGroup) {
                $name = $logGroup['logGroupName'];
                if (preg_match('/'.$groupPattern.'/', $name)) {
                    $table->addRow([
                        $logGroup['logGroupName'],
                        isset($logGroup['retentionInDays']) ? $logGroup['retentionInDays'] : 'Never',
                        round($logGroup['storedBytes'] / (1024*1024))
                    ]);
                    $totalBytes += $logGroup['storedBytes'];
                }
            }
            $nextToken = $result->get("nextToken");
        } while ($nextToken);


        $table->render();

        $output->writeln('Total size: ' . $this->formatBytes($totalBytes));
    }

    protected function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

}
