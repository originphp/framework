<?php
namespace App\Command;

use Origin\Command\Command;

class SaySomethingCommand extends Command
{
    protected $name = 'say-hello'; // non convention

    protected $description = 'A command to say something';

    public function initialize() : void
    {
        $this->addArgument('what', [
            'description' => 'What should be said','require' => true,
        ]);
        $this->addOption('color', [
            'description' => 'color to use',
            'default' => 'white',
        ]);
    }

    public function execute() : void
    {
        $what = $this->arguments('what');
        $color = $this->options('color');
        $this->out("<{$color}>Hello {$what}</{$color}>");
    }
}
