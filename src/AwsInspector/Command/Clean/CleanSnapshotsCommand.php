<?php

namespace AwsInspector\Command\Clean;

use AwsInspector\SdkFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class CleanSnapshotsCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('clean:orphaned-snapshots-from-createimage')
            ->setDescription('Delete snapshots created via CreateImage where the AMI does not exist anymore');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $iam = new \AwsInspector\Model\Iam\Repository();
        $accountId = $iam->findCurrentUser()->getAccountId();

        $output->writeln('Owner: ' . $accountId);

        $ec2Client = SdkFactory::getClient('EC2'); /* @var $ec2Client \Aws\Ec2\Ec2Client */

        $res = $ec2Client->describeImages([
            'Owners' => [$accountId]
        ]);

        $activeImageIds = array_flip($res->search('Images[].ImageId'));

        $res = $ec2Client->describeSnapshots([
            // 'Filters' => [['Name' => 'owner-id','Values' => [$accountId]]]
            'OwnerIds' => [$accountId]
        ]);

        $orphanSnapshots = [];

        foreach ($res->get('Snapshots') as $snapshotData) {
            $description = $snapshotData["Description"];
            // Created by CreateImage(i-ee0c7564) for ami-9945d0ea from vol-e4b6ff16
            // if (preg_match('/^Created by CreateImage\(i-.*\) for \(ami-.*\) from \(vol-.*\)$/', $description)) {
            if (preg_match('/^Created by CreateImage\(i-.*\) for (ami-.+) from vol-.+/', $description, $matches)) {
                $amiId = $matches[1];
                if (isset($activeImageIds[$amiId])) {
                    $output->writeln('Found active AMI: ' . $amiId);
                } else {
                    $output->writeln('AMI not found: ' . $amiId);
                    $orphanSnapshots[] = $snapshotData['SnapshotId'];
                }
            }
        }

        foreach ($orphanSnapshots as $snapshotId) {
            $output->writeln('Deleting ' . $snapshotId);
            $result = $ec2Client->deleteSnapshot([
                'SnapshotId' => $snapshotId
            ]);
        }

    }

}
