<?php
namespace App\Command;

use Origin\Command\Command;

class CacheResetCommand extends Command
{
    protected $name = 'cache:reset';

    protected $description = 'A command to reset the cache';

    public function initialize() : void
    {
    }

    public function execute() : void
    {
        $this->out('Cache has been reset');
    }
}
