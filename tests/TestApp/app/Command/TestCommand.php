<?php
namespace App\Command;

use Origin\Command\Command;

class TestCommand extends Command
{
    protected $name = 'test';

    protected $description = 'A command for testing';

    public function initialize()
    {
        $this->addArgument('test', ['description' => 'Which test','required' => true]);
    }

    public function execute()
    {
        $test = $this->arguments('test');
        $this->{$test}();
    }

    public function say()
    {
        $this->out('Hello world!');
    }

    public function ask()
    {
        $answer = $this->io->askChoice('Yes or no', ['yes','no']);
        $this->out('You entered ' . $answer);
    }

    public function omg()
    {
        $this->throwError('OMG! Its all Gone pete tong');
    }

    public function empty()
    {
        // do nothing
    }
}