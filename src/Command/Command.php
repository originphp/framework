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
 *
 * @see         https://www.originphp.com
 *
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Command;

use Origin\Console\ConsoleIo;
use Origin\Console\ArgumentParser;
use Origin\Console\Exception\StopExecutionException;
use Origin\Console\Exception\ConsoleException;
use Origin\Model\Exception\MissingModelException;
use Origin\Model\ModelRegistry;
use SebastianBergmann\Environment\Console;
use Origin\Console\CommandRunner;

class Command
{
    /**
     * Default error code.
     *
     * @var int
     */
    const ERROR = 1;

    /**
     * Default success code.
     *
     * @var int
     */
    const SUCCESS = 0;

    /**
     * Console Input/Output Object.
     *
     * @var \Origin\Console\ConsoleIo
     */
    public $io = null;

    /**
     * The name of the command.
     *
     * @var string
     */
    protected $name = null;
    /**
     * Holds the description for this command. This is shown before help.
     *
     * @var string|array
     */
    protected $description = '';

    /**
     * This is displayed after help.
     *
     * @var string|array
     */
    protected $epilog = null;

    /**
     * Command argument configuration.
     *
     * @var array
     */
    protected $commandArguments = [];
    /**
     * Command options configuration.
     *
     * @var array
     */
    protected $commandOptions = [];

    protected $arguments = [];

    protected $options = [];

    protected $verbose = false;

    /**
     * A list of methods used as subcommand
     *
     * @var array
     */
    protected $subCommands = [];

    /**
     * Undocumented variable.
     *
     * @var \Origin\Console\ArgumentParser;
     */
    protected $parser = null;

    public function __construct(ConsoleIo $io = null)
    {
        if ($io === null) {
            $io = new ConsoleIo();
        }
        $this->io = $io;

        $this->parser = new ArgumentParser();

        $this->addOption('help', ['short' => 'h', 'description' => 'Displays this help message', 'type' => 'boolean']);
        $this->addOption('verbose', ['short' => 'v', 'description' => 'Displays additional output (if available)', 'type' => 'boolean']);

        $this->validateName($this->name);
        $this->description($this->description); // Convert from array if set using this
    }

    /**
     * The initialize hook, called before command is executed. Setup your arguments and options for parsing.
     */
    public function initialize()
    {
    }

    /**
     * Runs another command from this command
     *
     * @param string $command
     * @param array $args  array of options e.g 
     *    $args = ['my_database','--datasource'=>'default','--help']
     * @return void
     */
    public function runCommand(string $command,array $args){
       $runner = new CommandRunner($this->io);
       $command = $runner->findCommand($command);
       if(!$command instanceof Command){
           throw new ConsoleException(sprintf('Command `%s` was not found'));
       }

       // Convert args
       $argv = [];
       foreach($args as $key => $value){
           if(is_int($key)){
               $argv[] = $value;
           }
           else{
               $argv[] = "{$key}={$value}"; 
           }
       }
       return $command->run($argv);
    }


    /**
     * Runs this command used by Command Runner
     *
     * @param array $args
     */
    public function run(array $args)
    {
        $this->initialize($args);

        $this->parser->setCommand($this->name);
        $this->parser->setDescription($this->description);
        $this->parser->setEpilog($this->epilog);
        
        $method = 'execute';
        // Extract First Argument if sub command
        if($this->subCommands){
            $subCommand = null;
            foreach($args as $i => $arg){
                if($arg[0] !== '-'){
                    $subCommand = $arg;
                    break;
                }
            }

            if($subCommand AND in_array($subCommand,$this->subCommands)){
                unset($args[$i]);
                $method = str_replace('-','_',$subCommand);
            }
            else{
                $args = ['--help']; // Force display help and disable execute
            }
        }

        try {
            list($options, $arguments) = $this->parser->parse($args);
        } catch (ConsoleException $ex) {
            $this->io->err('<exception> ERROR </exception> <text>'.$ex->getMessage().'</text>');
            $this->io->nl();
            $this->out($this->parser->usage());

            return false;
        }
        $this->options = $options;
        $this->arguments = $arguments;

        // Enable verbosity
        if ($this->options('verbose')) {
            $this->verbose = true;
        }

        if ($this->options('help')) {
            $this->displayHelp();

            return true;
        }
        $this->{$method}();
        return true;
    }

    /**
     * Gets an options or all options.
     *
     * @param string $name
     * @return mixed
     */
    public function options(string $name = null)
    {
        if ($name === null) {
            return $this->options;
        }
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }

        return null;
    }

    /**
     * Gets an argument or aguments.
     *
     * @param string $name
     * @return mixed
     */
    public function arguments(string $name = null)
    {
        if ($name === null) {
            return $this->arguments;
        }
        if (isset($this->arguments[$name])) {
            return $this->arguments[$name];
        }

        return null;
    }

    /**
     * This will set or get the name. Note. Setting name here does not change the command name since name is taken after
     * construction of the object without running any methods.
     * @internal not validating name here. Changes here only affect help usage ouput e.g console app setting name
     * requires spaces.
     * @param string $name
     * @return void
     */
    public function name(string $name = null)
    {
        if ($name === null) {
            return $this->name;
        }
        
        $this->name = $name;
    }

    protected function validateName(string $name)
    {
        // Valid syntax name, some-name, app:some-name, app:name-a:name-b
        if (!preg_match_all('/^[a-z][a-z-]++(?:\:[a-z-]++)*$/', $name)) {
            throw new ConsoleException(sprintf('Command name `%s` is invalid',$name));
        }
    }

    /**
     * Sets the descripton.
     *
     * @param string|array $description
     */
    public function description($description = null)
    {
        if ($description === null) {
            return $this->description;
        }
        if(is_array($description)){
            $description = implode("\n",$description);
        }
        $this->description = $description;
    }

    /**
     * Adds an available option or flag.
     *
     * @param string $name
     * @param array  $options Options include help:help text short: short option e.g -ds, boolean=true, required
     *  - description: the help description
     *  - short: short option when using -. e.g. -ds
     *  - required: default false
     *  - type: string, integer, boolean, array, hash
     *  - default: default value
     */
    public function addOption(string $name, array $options = [])
    {
        $this->parser->addOption($name, $options);
    }

    /**
     * Adds a available.
     *
     * @param string $name    name of the command
     * @param array  $options
     *  - description: the help description
     *  - type: string, integer, array, hash
     *  - required: default false
     */
    public function addArgument(string $name, array $options = [])
    {
        $this->parser->addArgument($name, $options);
    }

    /**
     * Add a method as a sub command.
     *
     * @param string $name lower case letters and hypens only, methods with hypens will be changed to underscore
     * @param array $options
     *  - description: the help description
     * @return void
     */
    public function addSubCommand(string $name, array $options = []){
        if(!preg_match('/^[a-z-]+$/',$name)){
            throw new ConsoleException(sprintf('Invalid sub command name `%s`.',$name));
        }
        $this->subCommands[] = $name;
        $this->parser->addCommand($name, $options);
    }

    /**
     * Displays the help.
     */
    public function displayHelp()
    {
        $content = $this->parser->help();

        $this->io->out($content);
    }

    /**
     * Aborts the execution of the command and sets the exit code as an error.
     *
     * @param string $status
     */
    public function abort(string $message = 'Command Aborted', $exitCode = self::ERROR)
    {
        throw new StopExecutionException($message, $exitCode);
    }

    /**
     * Exits the command succesfully.
     *
     * @param string $status
     */
    public function exit(string $message = 'Exited Command', $exitCode = self::SUCCESS)
    {
        throw new StopExecutionException($message, $exitCode);
    }

    /**
     * If verbose is enabled then out put passed here will be displayed.
     *
     * @param string|array $message
     */
    public function debug($message)
    {
        if ($this->verbose) {
            $message = $this->addTags('debug', $message);
            $this->out($message);
        }
    }

    /**
     * Displays styled info text.
     *
     * @param string|array $message
     */
    public function info($message)
    {
        $message = $this->addTags('info', $message);
        $this->out($message);
    }

    /**
     * displays styled notices.
     *
     * @param string|array $message
     */
    public function notice($message)
    {
        $message = $this->addTags('notice', $message);
        $this->out($message);
    }

    /**
     * Displays styled warnings.
     *
     * @param string|array $message
     */
    public function warning($message)
    {
        $message = $this->addTags('warning', $message);
        $this->out($message);
    }

    /**
     * Displays styled warnings.
     *
     * @param string|array $message
     */
    public function success($message)
    {
        $message = $this->addTags('success', $message);
        $this->out($message);
    }

    /**
     * Displays styled warnings.
     *
     * @param string|array $message
     */
    public function error($message)
    {
        $message = $this->addTags('error', $message);
        $this->out($message);
    }

    protected function addTags(string $tag, $message)
    {
        if (is_array($message)) {
            foreach ($message as $i => $line) {
                $message[$i] = "<{$tag}>{$line}</{$tag}>";
            }

            return $message;
        }

        return "<{$tag}>{$message}</{$tag}>";
    }

    /**
     * Displays an error message and aborts the command.
     *
     * @param string $title
     * @param string $message
     */
    public function throwError(string $title, string $message = null)
    {
        $msg = "<exception> ERROR </exception> <heading>{$title}</heading>\n";
        if ($message) {
            $msg .= "<text>{$message}</text>\n";
        }
        $this->io->err($msg);
        $this->abort($title);
    }

    /**
     * Place the command logic here.
     */
    public function execute()
    {
    }

    /**
     * A wrapper for the IO out.
     *
     * @param array|string $message
     */
    public function out($message)
    {
        $this->io->out($message);
    }

    /**
     * Loads a model, uses from registry or creates a new one.
     *
     * @param string $model
     *
     * @return \Origin\Model\Model
     */
    public function loadModel(string $model)
    {
        if (isset($this->{$model})) {
            return $this->{$model};
        }

        $this->{$model} = ModelRegistry::get($model);

        if ($this->{$model}) {
            return $this->{$model};
        }
        throw new MissingModelException($model);
    }
}
