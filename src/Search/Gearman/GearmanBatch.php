<?php

namespace eLife\Search\Gearman;

final class GearmanBatch
{
    private $commands;

    public function __construct($commands)
    {
        $this->commands = $commands;
    }

    public function getCommands()
    {
        return $this->commands;
    }

    public function add($command)
    {
        $this->commands[] = $command;
    }
}
