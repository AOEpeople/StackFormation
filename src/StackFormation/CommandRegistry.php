<?php

namespace StackFormation;

class CommandRegistry
{

    public static function getCommands()
    {
        return [
            new Command\Stack\ListCommand(),
            new Command\Stack\ObserveCommand(),
            new Command\Stack\DeleteCommand(),
            new Command\Stack\TimelineCommand(),
            new Command\Stack\Show\OutputsCommand(),
            new Command\Stack\Show\ParametersCommand(),

            new Command\Blueprint\DeployCommand(),
            new Command\Blueprint\DiffCommand(),
            new Command\Blueprint\ValidateCommand(),
            new Command\Blueprint\Show\TemplateCommand(),
            new Command\Blueprint\Show\ParametersCommand(),
            new Command\Blueprint\CompareAllCommand()

            //new Command\UpdateRoute53AliasCommand(),
        ];
    }
}
