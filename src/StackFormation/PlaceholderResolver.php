<?php

namespace StackFormation;

use Aws\CloudFormation\Exception\CloudFormationException;

class PlaceholderResolver {

    protected $dependencyTracker;
    protected $stackFactory;

    public function __construct(DependencyTracker $dependencyTracker, StackFactory $stackFactory)
    {
        $this->dependencyTracker = $dependencyTracker;
        $this->stackFactory = $stackFactory;
    }

    /**
     * Resolve placeholders
     */
    public function resolvePlaceholders($string, Blueprint $blueprint=null, $type=null)
    {
        $originalString = $string;

        // craft exception message appendix
        $exceptionMessageAppendix = [];
        if ($blueprint) { $exceptionMessageAppendix[] = 'Blueprint: ' . $blueprint->getName(); }
        if ($type) { $exceptionMessageAppendix[] = 'Type:' . $type; }
        if (count($exceptionMessageAppendix)) {
            $exceptionMessageAppendix = ' (' . implode(', ', $exceptionMessageAppendix) . ')';
        } else {
            $exceptionMessageAppendix = '';
        }

        // {env:...}
        $string = preg_replace_callback(
            '/\{env:([^:\}\{]+?)\}/',
            function ($matches) use ($exceptionMessageAppendix) {
                $this->dependencyTracker->trackEnvUsage($matches[1]);
                if (!getenv($matches[1])) {
                    throw new \Exception("Environment variable '{$matches[1]}' not found$exceptionMessageAppendix");
                }
                return getenv($matches[1]);
            },
            $string
        );

        // {env:...:...} (with default value if env var is not set)
        $string = preg_replace_callback(
            '/\{env:([^:\}\{]+?):([^:\}\{]+?)\}/',
            function ($matches) {
                $this->dependencyTracker->trackEnvUsage($matches[1], true);
                if (!getenv($matches[1])) {
                    return $matches[2];
                }
                return getenv($matches[1]);
            },
            $string
        );

        // {var:...}
        $vars = $blueprintName ? $this->getConfig()->getBlueprintVars($blueprintName) : $this->getConfig()->getGlobalVars();
        $string = preg_replace_callback(
            '/\{var:([^:\}\{]+?)\}/',
            function ($matches) use ($vars, $exceptionMessageAppendix) {
                if (!isset($vars[$matches[1]])) {
                    throw new \Exception("Variable '{$matches[1]}' not found$exceptionMessageAppendix");
                }
                return $vars[$matches[1]];
            },
            $string
        );

        // {tstamp}
        static $time;
        if (!isset($time)) {
            $time = time();
        }
        $string = str_replace('{tstamp}', $time, $string);

        // {output:...:...}
        $string = preg_replace_callback(
            '/\{output:([^:\}\{]+?):([^:\}\{]+?)\}/',
            function ($matches) use ($exceptionMessageAppendix) {
                try {
                    $this->dependencyTracker->trackStackDependency('output', $matches[1], $matches[2]);
                    return $this->stackFactory->getStack($matches[1])->getOutput($matches[2]);
                } catch (CloudFormationException $e) {
                    $extractedMessage = Helper::extractMessage($e);
                    throw new \Exception("Error resolving '{$matches[0]}'$exceptionMessageAppendix (CloudFormation error: $extractedMessage)");
                }
            },
            $string
        );

        // {resource:...:...}
        $string = preg_replace_callback(
            '/\{resource:([^:\}\{]+?):([^:\}\{]+?)\}/',
            function ($matches) use ($blueprintName, $type) {
                try {
                    $this->dependencyTracker->trackStackDependency('resource', $matches[1], $matches[2]);
                    return $this->stackFactory->getStack($matches[1])->getResource($matches[2]);
                } catch (CloudFormationException $e) {
                    $extractedMessage = Helper::extractMessage($e);
                    throw new \Exception("Error resolving '{$matches[0]}' (Blueprint: $blueprintName, Type: $type) (CloudFormation error: $extractedMessage)");
                }
            },
            $string
        );

        // {parameter:...:...}
        $string = preg_replace_callback(
            '/\{parameter:([^:\}\{]+?):([^:\}\{]+?)\}/',
            function ($matches) use ($blueprintName, $type) {
                try {
                    $this->dependencyTracker->trackStackDependency('parameter', $matches[1], $matches[2]);
                    return $this->stackFactory->getStack($matches[1])->getParameter($matches[2]);
                } catch (CloudFormationException $e) {
                    $extractedMessage = Helper::extractMessage($e);
                    throw new \Exception("Error resolving '{$matches[0]}' (Blueprint: $blueprintName, Type: $type) (CloudFormation error: $extractedMessage)");
                }
            },
            $string
        );

        // {clean:...}
        $string = preg_replace_callback(
            '/\{clean:([^:\}\{]+?)\}/',
            function ($matches) {
                return preg_replace('/[^-a-zA-Z0-9]/', '', $matches[1]);
            },
            $string
        );

        // recursively continue until everything is replaced
        if ($string != $originalString) {
            $string = $this->resolvePlaceholders($string, $blueprintName, $type);
        }

        return $string;
    }

}