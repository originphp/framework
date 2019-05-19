<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Console;


use Origin\Command\Command;
use Origin\Console\ConsoleIo;
use Origin\Console\ConsoleHelpFormatter;
use Origin\Console\ArgumentParser;

use Origin\Console\Exception\ConsoleException;

/** 
 * If you only add one command, by default it will become a single command application and the command
 * will be run automatically.
 * 
 * @example:
 * require __DIR__ . '/vendor/originphp/originphp/src/bootstrap.php';
 * use Origin\Console\ConsoleApplication;
 * 
 * use App\Command\CreateTableCommand;
 * use App\Command\DropTableCommand;
 * 
 * $consoleApplication = new ConsoleApplication('db',['DB application for creating and dropping tables']);
 * $consoleApplication->addCommand('create', new CreateTableCommand());
 * $consoleApplication->run();
 */


class ConsoleApplication
{

    /**
     * Name of the application. Should be the same name
     * as the bash script since this will be used to display help.
     *
     * @var string
     */
    protected $name = 'app';

    /**
     * A description that appears in the help for this console app
     *
     * @var string
     */
    protected $desription = '';

     /**
     * Holds the command list
     *
     * @var array
     */
    protected $commands = [];

    /**
     * 
     *
     * @param string $name should be the same as the executable file as this will be used in help
     * @param string|array $desription description shown in help
     */
    public function __construct(string $name = 'app',$desription = null){
        $this->name = $name;
        $this->desription = $desription;

        $this->argumentParser = new ArgumentParser();

        $this->argumentParser->addOption('help', [
            'short'=>'h','description'=>'Displays this help message','type'=>'boolean'
            ]);
   
        $this->initialize();
    }

    public function initialize(){

    }

    /**
     * Runs the console application
     *
     * @param array $args default is argv
     * @return bool
     */
    public function run(array $args = null,ConsoleIo $io=null){
       
        if($args === null){
            global $argv;
            $args = $argv;
        }

        if($io === null){
            $io = new ConsoleIo();
        }
        array_shift($args);
        if(empty($this->commands)){
            $this->io->error('No commands have been added to this application.');
            return false;
        }
  
        try {
            list($options,$arguments) = $this->argumentParser->parse($args);
        } catch (ConsoleException $ex) {
            $this->throwError($ex->getMessage());
        }

        # If its one command application load the first one by default
        if(count($this->commands) === 1 AND empty($args)){
            $args = [$this->commands[0]];
        }

        if(isset($options['help']) OR empty($args)){
            $this->displayHelp($io);
            return true;
        }

        $command = array_shift($args);
        if(!isset($this->{$command}) OR !$this->{$command} instanceof Command){
            $this->io->error("Invalid command {$command}.");
            return false;
        }

        return $this->{$command}->run($args);
    }

    public function displayHelp(ConsoleIo $io){
        $formatter = new ConsoleHelpFormatter();
        if($this->desription){
            $formatter->setDescription($this->desription);
        }
        $formatter->setUsage(["{$this->name} command [options] [arguments]"]);
        $commands = [];
        foreach($this->commands as $name){
            $commands[$name] = $this->{$name}->description();
        }
        $formatter->setCommands($commands);
        $io->out($formatter->generate());
    }

    /**
     * Registers a command
     *
     * @param string $alias
     * @param Command $command
     * @return void
     */
    public function addCommand(string $alias, Command $command){

        $command->name($this->name . ' ' . $alias);

       $this->{$alias} = $command;
       $this->commands[] = $alias;
    }
}
