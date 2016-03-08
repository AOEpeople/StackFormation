<?php

namespace StackFormation;

class CommandRegistry
{

    public static function getCommands()
    {
        return [
            new Command\DeployCommand(),
            new Command\ListCommand(),
            new Command\ObserveCommand(),
            new Command\DeleteCommand(),
            new Command\ShowLocalCommand(),
            new Command\ShowTemplateCommand(),
            new Command\ShowParametersCommand(),
            new Command\ShowLiveCommand(),
            new Command\TemplateDiffCommand(),
            new Command\ValidateTemplateCommand(),
            new Command\UpdateRoute53AliasCommand(),
            new Command\TimelineCommand(),
            new Command\CompareAllCommand()
        ];
    }
}
