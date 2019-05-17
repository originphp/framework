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

use Origin\Core\Resolver;
use Origin\Command\Command;
use Origin\Console\ConsoleIo;
use Origin\Core\Inflector;
use Origin\Console\Exception\StopExecutionException;

/**
 * Package commands into a script and run directly.
 * 
 * require dirname(__DIR__).'/bootstrap.php';
 * use Origin\Console\ConsoleApplication;
 * 
 * $consoleApplication = new ConsoleApplication();
 * $consoleApplication->addCommand(new CreateTableCommand());
 * $consoleApplication->run();
 */

class ConsoleApplication
{
    /**
     * Undocumented variable
     *
     * @var \Origin\Console\ConsoleIo
     */
    protected $io = null;

    /**
     * Holds the command list
     *
     * @var array
     */
    protected $commands = [];

    public function __construct(ConsoleIo $io=null){
        if($io === null){
            $io = new ConsoleIo();
        }
        $this->io = $io;
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
    public function run(array $args = null){
       
        if($args === null){
            global $argv;
            $args = $argv;
        }
        array_shift($args);
        if(empty($this->commands)){
            $this->io->error('No commands have been added to this application.');
            return false;
        }
  
        $command = array_shift($args);
        if(!isset($this->{$command}) OR !$this->{$command} instanceof Command){
            $this->io->error("Invalid command {$command}.");
            return false;
        }

        return $this->{$command}->run($args);
    }

    /**
     * Add a command to this application
     *
     * @param \Origin\Command\Command $command
     * @return void
     */
    public function addCommand(Command $command){
       $name = $this->commandName(get_class($command));
       $this->{$name} = $command;
       $this->commands[] = $name;
    }

    /**
     * Gets the command name from the class
     *
     * @param string $className Full classname with namespace
     * @return void
     */
    protected function commandName(string $className){
        list($namespace,$class) = namespaceSplit($className);
        $name = substr($class,0,-7); // remove Command
        $name = Inflector::underscore($name); // convert to underscore
        return str_replace('_','-',$name);
    }

     /**
     * Loads commands using a name
     *
     * Examples
     *
     * $this->loadCommand('Hello');
     * $this->loadCommand('App\Command\Db\CreateDatabaseCommand');
     *
     * @param string $name
     * @return void
     */
    public function loadCommand(string $command){
        $className = Resolver::className($command, 'Command', 'Command');
        $this->addCommand(new $className);
    }
}
