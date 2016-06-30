<?php

namespace StackFormation\Command\Stack;

use StackFormation\Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TimelineCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:timeline')
            ->setDescription('Generate HTML timeline of stack events')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForStack($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $this->getStackFactory()->getStack($input->getArgument('stack'));
        Helper::validateStackname($stack);

        $events = $stack->getEvents();

        $groups = [];
        $itemsByGroup = [];
        foreach ($events as $event) {
            $groupId = $event['LogicalResourceId'];
            if (!isset($groups[$groupId])) {
                $groups[$groupId] = [
                    'id' => $groupId,
                    'content' => '<strong>'.$groupId . '</strong><br /><em>'.$event["ResourceType"] . '</em>',
                    'first_event' => $event["Timestamp"]
                ];
            }
            $status = $event["ResourceStatus"];
            if (isset($itemsByGroup[$groupId])) {
                $lastItem = end($itemsByGroup[$groupId]);
                $lastKey = key($itemsByGroup[$groupId]);
                if ($lastItem['status'] == $status) {
                    continue;
                }
                $duration = strtotime($event["Timestamp"]) - strtotime($itemsByGroup[$groupId][$lastKey]['start']);
                if ($duration > 15) {
                    $content = floor($duration / 60) . ':' . sprintf("%02d", (int)$duration % 60);
                    $itemsByGroup[$groupId][$lastKey]['content'] = $content;
                }
                $itemsByGroup[$groupId][$lastKey]['end'] = $event["Timestamp"];
            }
            $tmp = [
                'className' => strtolower($status) . ' ' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $event["ResourceType"])),
                // 'id'=> $event["EventId"],
                'group' => $groupId,
                'start' => $event["Timestamp"],
                'content' => '&nbsp;',
                'status' => $status
            ];
            if (!preg_match('/_IN_PROGRESS$/', $status)) {
                $tmp['type'] = 'point';
            }
            if (preg_match('/_IN_PROGRESS$/', $status)) {
                $tmp['className'] .= ' in_progress';
            }
            if (preg_match('/_COMPLETE$/', $status)) {
                $tmp['className'] .= ' complete';
            }
            if (preg_match('/_FAILED$/', $status)) {
                $tmp['className'] .= ' failed';
            }
            $itemsByGroup[$groupId][] = $tmp;
        }

        $items = [];
        foreach ($itemsByGroup as $group) {
            $items = array_merge($items, $group);
        }

        $timeline = '<!DOCTYPE HTML>
        <html>
        <head>
            <title>AWS CloudFormation Stack Event Visualization</title>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.4/moment.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/vis/4.12.0/vis.min.js"></script>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/vis/4.12.0/vis.min.css" rel="stylesheet" type="text/css" />
            <style>
                body, html { font-family: arial, sans-serif; font-size: 11pt; }
                #visualization { box-sizing: border-box; width: 100%; height: 300px; }
                .vis-item { border-width: 0; }
                .vis-item.in_progress { background-color: orange; }
                .vis-item.failed { border-color: red; border-width: 8px; border-radius: 8px; }
                .vis-item.complete { border-color: green; border-width: 8px; border-radius: 8px; }
                em { font-size: smaller; }
                .vis-labelset .vis-label { background-color: #eee; }
                .vis-item.aws--cloudformation--waitcondition.in_progress {
                    background: repeating-linear-gradient(to right, #f6ba52, #f6ba52 10px, #ffd180 10px, #ffd180 20px);
                }
            </style>
        </head>
        <body>
        <h1>'.$stack->getName().'</h1>
        <div id="visualization"></div>
        <script>
            var groups = new vis.DataSet();
            var items = new vis.DataSet();
            groups.add(' . json_encode(array_values($groups)) .');
            items.add(' .  json_encode(array_values($items)).');
            var timeline = new vis.Timeline(document.getElementById("visualization"));
            timeline.setOptions({ stack: false, groupOrder: "first_event" });
            timeline.setGroups(groups);
            timeline.setItems(items);
        </script>
        </body>
        </html>';

        $output->writeln($timeline);
    }
}
