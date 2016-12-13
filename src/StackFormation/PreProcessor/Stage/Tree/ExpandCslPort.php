<?php

namespace StackFormation\PreProcessor\Stage\Tree;

use StackFormation\PreProcessor\Stage\AbstractTreePreProcessorStage;
use StackFormation\PreProcessor\Rootline;

class ExpandCslPort extends AbstractTreePreProcessorStage
{
    /**
     * This one converts "Port" into "FromPort" and "ToPort" and will resolve comma-separated lists
     *
     * @param string $path
     * @param string $value
     * @param Rootline $rootLineReferences
     * @return bool
     */
    function invoke($path, $value, Rootline $rootLineReferences) {
        if (!preg_match('+/SecurityGroupIngress/.*/Port$+', $path)) {
            return false;  // indicate that nothing has been touched
        }

        $parentRootlineItem = $rootLineReferences->parent(1); /* @var $parentRootlineItem RootlineItem */
        $parent = $parentRootlineItem->getValue(); /* @var $parent RecursiveArrayObject */

        $grandParentRootlineItem = $rootLineReferences->parent(2); /* @var $grandParentRootlineItem RootlineItem */
        $grandParent = $grandParentRootlineItem->getValue(); /* @var $grandParent RecursiveArrayObject */

        // remove original item
        $grandParent->offsetUnset($parentRootlineItem->getKey());

        // add a new line for every csl item
        foreach (explode(',', $value) as $port) {
            $newItem = clone $parent;
            $newItem->FromPort = $port;
            $newItem->ToPort = $port;
            $newItem->offsetUnset('Port');
            $grandParent->append($newItem);
        }

        return true; // indicate that something has changed
    }
}
