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
use Origin\Core\Plugin;
use Origin\Core\Configure;

use Origin\Console\ConsoleIo;
use Origin\Core\Inflector;
use Origin\Core\Resolver;
use Origin\Console\Exception\StopExecutionException;


class CommandRunner
{
    protected $commands = [];

    /**
     * Holds a list of namespaces in array ['namespace'=>'path']
     *
     * @var array
     */
    protected $namespaces = [];

       /**
     * Undocumented variable
     *
     * @var \Origin\Console\ConsoleIo
     */
    protected $io = null;

    public function __construct(ConsoleIo $io=null){
        if($io === null){
            $io = new ConsoleIo();
        }
        $this->buildNamespaceMap();
    }

    protected function buildNamespaceMap(){
        $this->namespaces = [
            Configure::read('App.namespace') => SRC . DIRECTORY_SEPARATOR . 'Command',
            'Origin' => ORIGIN . DIRECTORY_SEPARATOR . 'Command',
        ];
       
        $plugins = Plugin::loaded();
        foreach($plugins as $plugin){
            $this->namespaces[$plugin] = PLUGINS . DS . Inflector::underscore($plugin) . DIRECTORY_SEPARATOR . 'src' . DS . 'Command';
        }
    }

    public function buildClassName(string $command){
        $folders = [];
        $commands = explode(':',$command);
        foreach($commands as $command){
            if($command){
                $folders[] = Inflector::camelize(str_replace('-','_',$command));
            }
        }
        return "Command\\" . implode('\\',$folders) . "Command";
    }
    
    public function run(array $args,ConsoleIo $io = null){
        if($io === null){
            $io = new ConsoleIo();
        }
        
        array_shift($args);
        if(empty($args)){
            $this->displayHelp($io);
            return;
        }

        $class = $this->findCommand($args[0]);
      
       if( $class){

        array_shift($args);
            try {
                $command = new $class($io);
                $command->run($args);
            } catch (StopExecutionException $ex) {
                return false;
            }
       }
       else{
           $io->error("Command {$args[0]} not found");
       }
      
       return false;
       
    }
    
    /**
     * Finds a class for 
     *
     * @param string $command
     * @return void
     */
    public function findCommand(string $command){
        $className = $this->buildClassName($command);
        foreach($this->namespaces as $ns => $directory){
            $needle = "{$ns}\\{$className}";
            if(class_exists($needle)){
                return $needle;
            }
        }
        return null;
    }

    public function discoverCommands(){
        $commands = [];
    
        # Level 1 
        foreach($this->namespaces as $namespace => $directory){
            $cmds = $this->getCommands($directory);
            $results = $this->getCommandDescriptions($cmds,$namespace);
            foreach($results as $cmd => $description){
                $commands[null][$cmd] = $description;
            }
            # App Commands
            $apps = $this->getApps($directory);
            foreach($apps as $app => $appDirectory){
                $cmdapp =  str_replace('_','-',Inflector::underscore($app));
                $cmds = $this->getCommands($appDirectory);             
                $results = $this->getCommandDescriptions($cmds,$namespace ,$app);
                foreach($results as $cmd => $appDescription){
                    $commands[$cmdapp][ $cmdapp .':' .$cmd] = $appDescription; 
                }
                $subApps = $this->getApps($appDirectory);
                
                # Sub Commands
                foreach($subApps as $subApp => $subAppDirectory){
                    $cmdSubApp =  str_replace('_','-',Inflector::underscore($subApp));
                    $cmds = $this->getCommands($subAppDirectory);
                  
                    $results = $this->getCommandDescriptions($cmds, $namespace ,"{$app}\\{$subApp}");
                 
                    foreach($results as $cmd => $subAppDescription){
                        $commands[$cmdapp][$cmdapp .':'. $cmdSubApp  .':' .$cmd] = $subAppDescription; 
                    }
                }
            }
        }
        
      
        return $commands;
    }

    protected function getCommandDescriptions(array $commands,string $namespace,string $subNamespace =""){
        $results = [];
       
        if($subNamespace){
            $subNamespace = "{$subNamespace}\\";
        }
        foreach($commands as $class => $directory){
            $className = "{$namespace}\\Command\\{$subNamespace}{$class}";
            $command = new $className($this->io);
            $results[$this->classToCommand($class)] = $command->description();
        }
        return $results;
    }

    protected function classToCommand(string $class){
        return str_replace('_','-',Inflector::underscore(substr($class,0,-7)));
    }

    protected function displayHelp(ConsoleIo $io){
        $commands = $this->discoverCommands();
    
        $out = [];
        $out[] = "<text>OriginPHP</text>";
        $out[] = "";
        $out[] = "<heading>Usage:</heading>";
        $out[] = "  <text>origin <command> [options] [arguments]</text>";
        $out[] = "";

        $maxLength = 0;
        foreach($commands as $group => $cmds){
            foreach($cmds as $cmd => $description){
                if(strlen($cmd) > $maxLength){
                    $maxLength = strlen($cmd);
                }
            }
        }

        foreach($commands as $group => $cmds){
            foreach($cmds as $cmd => $description){
                $cmd = str_pad($cmd,$maxLength + 2,' ',STR_PAD_RIGHT);
                $out[] =  "<code>{$cmd}</code><text>{$description}</text>";
            }
            $out[] = "";
        }
       $io->out($out);
    }
    /*
    
    }*/

   

    /**
     * Gets a list of folders
     *
     * @param string $directory
     * @return void
     */
    protected function getApps(string $directory){
        $directories = [];
        $results = glob($directory . '/*' , GLOB_ONLYDIR);
        foreach($results as  $directory){
        
            $directories[basename($directory)] = $directory;
        }
        return $directories;
    }

    /**
     * Gets commands from a directory
     *
     * @param string $directory
     * @return array
     */
    protected function getCommands(string $directory){
        $ignore = ['Command.php'];
    
        $result = [];
     
        if (file_exists($directory)) {
            $files = scandir($directory);
            foreach ($files as $file) {
                if (substr($file, -11) === 'Command.php' and !in_array($file, $ignore)) {
                    $result[substr($file, 0, -4)] = $directory . DS . $file;
                }
            }
        }
        
        return $result;
    }

  
   
}
