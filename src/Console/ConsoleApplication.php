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
use Origin\Core\Inflector;
use Origin\Core\Resolver;
use Origin\Console\Exception\StopExecutionException;
use Origin\Core\Plugin;

class ConsoleApplication

{

    /**
     * 
     *
     * Command naming structure
     * multi-word
     * multi-word:multi-function
     * multi-word:multi-function:nested-:abc
     * 
     * @param array $args
     * @param ConsoleIo $io
     * @return void
     */
    public function run(array $args,ConsoleIo $io = null){
        if($io === null){
            $io = new ConsoleIo();
        }
     
       array_shift($args);
       if(empty($args)){
           $this->displayHelp($io );
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
      
       return false;
    }

    protected function displayHelp(ConsoleIo $io){
        $commands = $this->discoverCommands();
    
        $out = [];
        $out[] = "<info>OriginPHP</info>";
        $out[] = "";
        $out[] = "<text>These are the commands found that you can run.</text>";
        $out[] = "";
        foreach($commands as $group => $cmds){
            $out[] = "<heading>{$group}</heading>";
            foreach($cmds as $cmd => $description){
                $cmd = str_pad($cmd,40,' ',STR_PAD_RIGHT);
                $out[] = " <code>{$cmd}</code><text>{$description}</text>";
            }
            $out[] = "";
        }
       $io->out($out);
    }

    /**
     * Undocumented function
     * - multi-word
     * - multi-word:multi-function
     * - multi-word:multi-function:nested-:abc
     * @param string $commandArg
     * @return string
     * @internal app-dev and app:dev load the same class. in theory this is correct.
     */
    protected function getClass(string $commandArg): string
    {
        $commands = explode(':',$commandArg);
        foreach($commands as &$command){
            $command = Inflector::camelize(str_replace('-','_',$command));
        }
        return implode('',$commands);
    }


    public function findCommand(string $commandArg){
        $class =  $this->getClass($commandArg);
        return Resolver::className($class,'Command','Command');
    }


    protected function commandSplit(string $name){
        $command = null;
        if (strpos($name, ':') !== false) {
            list($command, $name) = explode(':', $name, 2);
        }
    
        return [$command, $name];
    }

    /**
     * Build an index of commands
     *
     * @return void
     */
    protected function discoverCommands(){
        
        $commands = [];
      
        $commands = $this->buildDescriptions($this->scandir(ORIGIN . DS. 'src' . DS . 'Command'),'Origin');
        $commands =  array_merge_recursive($commands,$this->buildDescriptions($this->scandir(SRC . DS . 'Command'),'App'));
        
        $plugins = Plugin::loaded();
        sort($plugins);
       
        foreach ($plugins as $plugin) {
            $results = $this->scandir(PLUGINS . DS . Inflector::underscore($plugin) . DS . 'src' . DS . 'Command');
            $commands =  array_merge_recursive($commands,$this->buildDescriptions($results ,$plugin));
        }

        return $commands;
    }

    protected function buildDescriptions(array $results,string $namespacePrefix='Origin'){
        $commands = [];
        foreach($results as $class => $filename){
            $class = "{$namespacePrefix}\Command\\{$class}";
            $command = new $class();
            list($app,$cmd) = $this->commandSplit($command->name());
            if(!isset($commands[$app])){
                $commands[$app] = [];
            }
            $commands[$app][$command->name()] = $command->description();
        }

        return $commands;
    }


    protected function scandir(string $folder)
    {
        $ignore = ['Command.php'];
    
        $result = [];
     
        if (file_exists($folder)) {
            $files = scandir($folder);
            foreach ($files as $file) {
                if (substr($file, -11) === 'Command.php' and !in_array($file, $ignore)) {
                    $result[substr($file, 0, -4)] = $folder . DS . $file;
                }
            }
        }
        
        return $result;
    }
 
}
