<?php 
namespace App\Console;

use Origin\Console\Shell;

class DummyShell extends Shell
{
    public function initialize()
    {
    }

    public function say()
    {
        $this->out('Hello world!');
    }
    public function ask()
    {
        $result = $this->in('Do you want to continue', ['yes','no']);
        $this->out('You entered ' .  $result);
    }

    public function test()
    {
        $this->out('Start');
        $this->error('OMG! Its all Gone pete tong');
        $this->out('Finish');
    }
}
