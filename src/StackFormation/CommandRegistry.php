<?php

namespace StackFormation;

class CommandRegistry
{

    public static function getCommands()
    {
        return [
            new Command\Stack\ListCommand(),
            new Command\Stack\TreeCommand(),
            new Command\Stack\ObserveCommand(),
            new Command\Stack\DeleteCommand(),
            new Command\Stack\TimelineCommand(),
            new Command\Stack\CompareAllCommand(),
            new Command\Stack\DiffCommand(),
            new Command\Stack\Show\OutputsCommand(),
            new Command\Stack\Show\ParametersCommand(),
            new Command\Stack\Show\ResourcesCommand(),
            new Command\Stack\Show\DependantsCommand(),

            new Command\Blueprint\DeployCommand(),
            new Command\Blueprint\ValidateCommand(),
            new Command\Blueprint\Show\TemplateCommand(),
            new Command\Blueprint\Show\ParametersCommand(),
            new Command\Blueprint\Show\DependenciesCommand(),
            new Command\Blueprint\Show\ChangesetCommand(),
            new Command\Blueprint\Show\StacknameCommand()
        ];
    }
}
