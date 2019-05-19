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

namespace Origin\Command;
use Origin\Console\ConsoleIo;
use Origin\Console\ArgumentParser;
use Origin\Console\Exception\StopExecutionException;
use Origin\Console\Exception\ConsoleException;
use Origin\Model\Exception\MissingModelException;
use Origin\Model\ModelRegistry;
use Origin\Core\Inflector;

class Command
{

  /**
     * Default error code
     *
     * @var int
     */
    const ERROR = 1;

    /**
     * Default success code
     *
     * @var int
     */
    const SUCCESS = 0;

    /**
     * Console Input/Output Object
     *
     * @var \Origin\Console\ConsoleIo
     */
    public $io = null;

    /**
     * The name of the command
     *
     * @var string
     */
    protected $name = null;
    /**
     * Holds the description for this command
     *
     * @var string
     */
    protected $description = '';

    /**
     * Command argument configuration
     *
     * @var array
     */
    protected $commandArguments = [];
   /**
     * Command options configuration
     *
     * @var array
     */
    protected $commandOptions = [];

    protected $arguments = [];

    protected $options = [];

    protected $verbose = false;

    protected $argumentParser = null;

    public function __construct(ConsoleIo $io=null){
        if($io === null){
            $io = new ConsoleIo();
        }
        $this->io = $io;

        $this->argumentParser = new ArgumentParser();

        $this->addOption('help', ['short'=>'h','description'=>'Displays this help message','type'=>'boolean']);
        $this->addOption('verbose',['short'=>'v','description'=>'Displays additional output (if available)','type'=>'boolean']);
   
        if (!preg_match('/^[a-z0-9-:]+$/',$this->name)) {
            throw new ConsoleException('Invalid name format my-app:lower-case');
        }
    }

    /**
     * The initialize hook, called before command is executed. Setup your arguments and options for parsing
     *
     * @return void
     */
    public function initialize(){}

    /**
     * Runs the command
     *
     * @param array $args
     * @return void
     */
    public function run(array $args){
        $this->initialize($args);

        try {
            list($options,$arguments) = $this->argumentParser->parse($args);
        } catch (ConsoleException $ex) {
            $this->throwError($ex->getMessage());
        }
        $this->options = $options;
        $this->arguments = $arguments;

        // Enable verbosity
        if($this->arguments('verbose')){
            $this->verbose = true;
        }
    
        if($this->options('help')){
            $this->displayHelp();
            return true;
        }
        $this->execute();
        
    }

    /**
     * Gets an options or all options
     *
     * @param string $name
     * @return mixed
     */
    public function options(string $name = null){
        if($name === null){
            return $this->options;
        }
        if(isset($this->options[$name])){
            return $this->options[$name];
        }
        return null;
    }

    /**
     * Gets an argument or aguments
     *
     * @param string $name
     * @return mixed
     */
    public function arguments(string $name = null){
        if($name === null){
            return $this->arguments;
        }
        if(isset($this->arguments[$name])){
            return $this->arguments[$name];
        }
        return null;
    }


    public function name(string $name = null){
        if($name === null){
            return $this->name;
        }
       
        $this->name = $name;
    }
    /**
     * Sets the descripton
     *
     * @param string $description
     * @return void
     */
    public function description(string $description = null ){
        if($description === null){
            return $this->description;
        }
        $this->description = $description;
    }

/**
     * Adds an available option or flag
     *
     * @param string $name
     * @param array $options Options include help:help text short: short option e.g -ds, boolean=true, required
     *  - help: the help description
     *  - short: short option when using -. e.g. -ds 
     *  - required: default false
     *  - type: string, integer, boolean, array, hash
     *  - default: default value
     * @return void
     */
    public function addOption(string $name, array $options = [])
    {
        $this->argumentParser->addOption($name,$options);
    }

    /**
     * Adds a available
     *
     * @param string $name name of the command
     * @param array $options 
     *   - help: the help description
     *   - type: string, integer, array, hash
     *   - required: default false
     * @return void
     */
    public function addArgument(string $name, array $options = [])
    {
        $this->argumentParser->addArgument($name,$options);
    }

    /**
     * Displays the help
     *
     * @return void
     */
    public function displayHelp(){
        $content = $this->argumentParser->help($this->name,$this->description);

        $this->io->out($content);
    }

     /**
     * Aborts the execution of the command and sets the exit code as an error
     *
     * @param string $status
     * @return void
     */
    public function abort(string $message = 'Command Aborted',$exitCode = self::ERROR)
    {
        throw new StopExecutionException($message,$exitCode);
    }

   /**
     * Exits the command succesfully
     *
     * @param string $status
     * @return void
     */
    public function exit(string $message = 'Exited Command',$exitCode = self::SUCCESS)
    {
        throw new StopExecutionException($message,$exitCode);
    }

    /**
     * If verbose is enabled then out put passed here will be displayed
     *
     * @param string $message
     * @return void
     */
    public function debug(string $message){
        if($this->verbose){
            $message = $this->addTags('debug',$message);
            $this->out($message);
        }
    }
    /**
     * Displays styled info text
     *
     * @param string $message
     * @return void
     */
    public function info(string $message){
        $message = $this->addTags('info',$message);
        $this->out($message);
    }

    /**
     * displays styled notices
     *
     * @param string $message
     * @return void
     */
    public function notice(string $message){
        $message = $this->addTags('notice',$message);
        $this->out($message);
    }

    /**
     * Displays styled warnings
     *
     * @param string $message
     * @return void
     */
    public function warning(string $message){
        $message = $this->addTags('warning',$message);
        $this->out($message);
    }

     /**
     * Displays styled warnings
     *
     * @param string $message
     * @return void
     */
    public function success(string $message){
        $message = $this->addTags('success',$message);
        $this->out($message);
    }

    /**
     * Displays styled warnings
     *
     * @param string $message
     * @return void
     */
    public function error(string $message){
        $message = $this->addTags('error',$message);
        $this->out($message);
    }


    protected function addTags(string $tag,$message){
        if(is_array($message)){
            foreach($message as $i => $line){
                $message[$i] = "<{$tag}>{$line}</{$tag}>";
            }
            return $message;
        }
        return "<{$tag}>{$message}</{$tag}>";
    }

    /**
     * Displays an error message and aborts the command
     *
     * @param string $title
     * @param string $message
     * @return void
     */
    public function throwError(string $title, string $message=null)
    {
        $msg = "<exception> ERROR </exception> <heading>{$title}</heading>\n";
        if ($message) {
            $msg  .= "<text>{$message}</text>\n";
        }
        $this->io->err($msg);
        $this->abort($title);
    }

    /**
     * Place the command logic here
     *
     * @return void
     */
    public function execute(){

    }

    /**
     * A wrapper for the IO out
     *
     * @param array|string $message
     * @return void
     */
    public function out($message){
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