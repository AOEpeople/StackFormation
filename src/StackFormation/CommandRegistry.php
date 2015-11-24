<?php

namespace StackFormation;


class CommandRegistry {

    public static function getCommands() {
        return [
            new \StackFormation\Command\DeployCommand(),
            new \StackFormation\Command\ListCommand(),
            new \StackFormation\Command\ObserveCommand(),
            new \StackFormation\Command\DeleteCommand(),
            new \StackFormation\Command\ShowLocalCommand(),
            new \StackFormation\Command\ShowTemplateCommand(),
            new \StackFormation\Command\ShowLiveCommand(),
            new \StackFormation\Command\TemplateDiffCommand()
        ];
    }

}