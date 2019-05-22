<?php
namespace App\Command;
use Origin\Command\Command;

class CacheCommand extends Command
{
    protected $name = 'cache';

    protected $description = 'A command to enable and disable cache.';

    public function initialize(){
        $this->addSubCommand('enable',['description'=>'Enables the cache',['method'=>'enable_this']]);
        $this->addSubCommand('disable',['description'=>'Disables the cache']);
    }

    public function enable_this(){
       $this->out('Cache has been enabled');
    }

     public function disable(){
       $this->out('Cache has been disabled');
    }
}