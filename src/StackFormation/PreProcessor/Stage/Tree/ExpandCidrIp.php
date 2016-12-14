<?php

namespace StackFormation\PreProcessor\Stage\Tree;

use StackFormation\PreProcessor\Stage\AbstractTreePreProcessorStage;
use StackFormation\PreProcessor\Rootline;
use StackFormation\PreProcessor\RootlineItem;
use StackFormation\PreProcessor\RecursiveArrayObject;

class ExpandCidrIp extends AbstractTreePreProcessorStage
{
    /**
     * This one will resolve comma-separated list of IP addresses
     *
     * @param string $path
     * @param string $value
     * @param Rootline $rootLineReferences
     * @return bool
     */
    function invoke($path, $value, Rootline $rootLineReferences) {
        if (!preg_match('+/SecurityGroupIngress/.*/CidrIp+', $path) || strpos($value, ',') === false) {
            return false; // indicate that nothing has been touched
        }

        $parentRootlineItem = $rootLineReferences->parent(1); /* @var $parentRootlineItem RootlineItem */
        $parent = $parentRootlineItem->getValue(); /* @var $parent RecursiveArrayObject */

        $grandParentRootlineItem = $rootLineReferences->parent(2); /* @var $grandParentRootlineItem RootlineItem */
        $grandParent = $grandParentRootlineItem->getValue(); /* @var $grandParent RecursiveArrayObject */

        // remove original item
        $grandParent->offsetUnset($parentRootlineItem->getKey());

        // add a new line for every csl item
        foreach (explode(',', $value) as $cidrIp) {
            $newItem = clone $parent;
            $newItem->CidrIp = $cidrIp;
            $grandParent->append($newItem);
        }

        return true; // indicate that something has changed
    }
}
