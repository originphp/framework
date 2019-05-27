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

use Origin\Console\ConsoleIo;
use Origin\Console\ConsoleHelpFormatter;
use Origin\Console\ArgumentParser;
use Origin\Core\Resolver;
use Origin\Exception\Exception;
use Origin\Console\Exception\ConsoleException;
use Origin\Core\LazyLoadContainer;

/** 
 * If you only add one command, by default it will become a single command application and the command
 * will be run automatically.
 * 
 * @example:
 * #!/usr/bin/env php
 * require __DIR__ . '/vendor/originphp/originphp/src/bootstrap.php';
 * use Origin\Console\ConsoleApplication;
 *  
 * $consoleApplication = new ConsoleApplication();
 * $consoleApplication->name('db');
 * $consoleApplication->description([
 *  'DB application for creating and dropping tables'
 * ]);
 * $consoleApplication->addCommand('create', 'CreateTable');
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
    protected $description =  null;

     /**
     * Holds the command list
     *
     * @var array
     */
    protected $commands = [];


    /**
     * Undocumented variable
     *
     * @var \Origin\Core\LazyLoadContainer
     */
    protected $commandRegistry = null;

    /**
     * Undocumented variable
     *
     * @var \Origin\Console\ConsoleIo
     */
    protected $io = null;

    /**
     * Constructor
     *
     * @param string $name should be the same as the executable file as this will be used in help
     * @param string|array $desription description shown in help
     */
    public function __construct(ConsoleIo $io = null){
   
        if($io === null){
            $io = new ConsoleIo();
        }
        $this->io = $io;

        $this->argumentParser = new ArgumentParser();

        $this->argumentParser->addOption('help', [
            'short'=>'h','description'=>'Displays this help message','type'=>'boolean'
            ]);
   
        $this->initialize();
        
        $this->commandRegistry = new LazyLoadContainer();

    }

    public function initialize(){

    }

     /**
     * Sets the name for the application
     *
     * @param string $name
     * @return string|null
     */
    public function name(string $name = null){
        if($name === null){
            return $this->name;
        }
        if(!preg_match('/^[a-z-]+$/',$name)){
            throw new ConsoleException(sprintf('Command App name `%s` is invalid',$name));
        }
        $this->name = $name;
    }

    /**
     * Sets the description
     *
     * @param string|array $description
     * @return string|null
     */
    public function description($description =null){
        if($description === null){
            return $this->description;
        }
        if(is_array($description)){
            $description = implode("\n",$description);
        }
        $this->description = $description;
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
            array_shift($args); // Remove script that is running
        }


        $commands = $this->commandRegistry->list();

        if(empty($commands)){
           throw new ConsoleException('No commands have been added to this application.');
        }
  
        try {
            list($options,$arguments) = $this->argumentParser->parse($args);
        } catch (ConsoleException $ex) {
            $this->io->error($ex->getMessage());
            return false;
        }

       
        # If its one command application load the first one by default
        if(count($commands) === 1 AND empty($args)){
            $args = [$commands[0]];
        }

        if(empty($args)){
            $this->displayHelp();
            return true;
        }

        $command = array_shift($args);

        try{
            $this->{$command} = $this->commandRegistry->get($command);
        }catch(Exception $ex){
            $this->io->error("Invalid command {$command}.");
            return false;
        }   
        # Configure Command
        $this->{$command}->io = $this->io;
        $this->{$command}->name($this->name . ' ' .$command);  // Rename for help
        return $this->{$command}->run($args);
    }

    /**
     * Displays the help for this app
     *
     * @return void
     */
    public function displayHelp(){
        $formatter = new ConsoleHelpFormatter();
      
        if($this->description){
            $formatter->setDescription($this->description);
        }
        $formatter->setUsage(["{$this->name} command [options] [arguments]"]);
        $commands = [];

        foreach($this->commandRegistry->list() as $name){
            $command = $this->commandRegistry->get($name);
            $commands[$name] = $command->description();
        }
        $formatter->setCommands($commands);
        $this->io->out($formatter->generate());
    }
    
    /**
     * A work in progress
     *
     * @param string $alias
     * @param string $name Cache,Plugin.Cache, App\Command\Custom\Cache
     * @return void
     */
    public function addCommand(string $alias, string $name)
    {
        if(!preg_match('/^[a-z-]+$/',$alias)){
            throw new ConsoleException(sprintf('Alias `%s` is invalid',$alias));
        }

        $className = Resolver::className($name, 'Command', 'Command');
        $this->commandRegistry->add($alias,$className);
    }
}