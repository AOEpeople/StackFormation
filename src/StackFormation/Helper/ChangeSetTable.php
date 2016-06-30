<?php

namespace StackFormation\Helper;

use StackFormation\Helper;

class ChangeSetTable extends \Symfony\Component\Console\Helper\Table {

    public function render(\Aws\Result $changeSetResult) {
        $this->setHeaders(['Action', 'LogicalResourceId', 'PhysicalResourceId', 'ResourceType', 'Replacement']);
        $this->setRows($this->getRows($changeSetResult));
        parent::render();
    }

    protected function getRows(\Aws\Result $changeSetResult) {
        $rows = [];
        foreach ($changeSetResult->search('Changes[]') as $change) {
            $resourceChange = $change['ResourceChange'];
            $rows[] = [
                // $change['Type'], // would this ever show anything other than 'Resource'?
                $this->decorateChangesetAction($resourceChange['Action']),
                $resourceChange['LogicalResourceId'],
                isset($resourceChange['PhysicalResourceId']) ? $resourceChange['PhysicalResourceId'] : '',
                $resourceChange['ResourceType'],
                isset($resourceChange['Replacement']) ? Helper::decorateChangesetReplacement($resourceChange['Replacement']) : '',
            ];
        }
        return $rows;
    }

    protected function decorateChangesetAction($changeSetAction) {
        switch ($changeSetAction) {
            case 'Modify': return "<fg=yellow>$changeSetAction</>";
            case 'Add': return "<fg=green>$changeSetAction</>";
            case 'Remove': return "<fg=red>$changeSetAction</>";
        }
        return $changeSetAction;
    }

}