<?php

namespace StackFormation\ValueResolver;

use Aws\CloudFormation\Exception\CloudFormationException;
use StackFormation\Exception\StackNotFoundException;
use StackFormation\Helper;

class StackOutput extends AbstractValueResolverStage
{

    public function invoke($string)
    {
        $string = preg_replace_callback(
            '/\{output:([^:\}\{]+?):([^:\}\{]+?)\}/',
            function ($matches) {
                try {
                    $this->dependencyTracker->trackStackDependency('output', $matches[1], $matches[2], $this->sourceBlueprint, $this->sourceType, $this->sourceKey);
                    return $this->getStackFactory()->getStackOutput($matches[1], $matches[2]);
                } catch (StackNotFoundException $e) {
                    throw new \Exception("Error resolving '{$matches[0]}'{$this->getExceptionMessageAppendix()}", 0, $e);
                } catch (CloudFormationException $e) {
                    $extractedMessage = Helper::extractMessage($e);
                    throw new \Exception("Error resolving '{$matches[0]}'{$this->getExceptionMessageAppendix()} (CloudFormation error: $extractedMessage)");
                }
            },
            $string
        );
        return $string;
    }

}
