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
use Origin\Core\Inflector;
use Origin\Console\Exception\StopExecutionException;
use Origin\Console\Exception\ConsoleException;

class CommandRunner
{

    /**
     * Holds the Command from RUN
     *
     * @var \Origin\Command\Command
     */
    protected $command = null;

    protected $commands = [];

    /**
     * Holds a list of namespaces in array ['namespace'=>'path'].
     *
     * @var array
     */
    protected $namespaces = [];

    /**
     * Undocumented variable.
     *
     * @var \Origin\Console\ConsoleIo
     */
    protected $io = null;

    protected $discovered = [];

    public function __construct(ConsoleIo $io = null)
    {
        if ($io === null) {
            $io = new ConsoleIo();
        }
        $this->io = $io;
    }

    /**
     * Builds a map of namespaces and directories. First the framework then App.
     */
    protected function buildNamespaceMap()
    {
        $this->namespaces = [
            'Origin' => ORIGIN.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Command',
            Configure::read('App.namespace') => SRC.DIRECTORY_SEPARATOR.'Command',
        ];

        $plugins = Plugin::loaded();
        foreach ($plugins as $plugin) {
            $this->namespaces[$plugin] = PLUGINS.DS.Inflector::underscore($plugin).DIRECTORY_SEPARATOR.'src'.DS.'Command';
        }
    }

    /**
     * Goes through discovery process.
     */
    protected function autoDiscover()
    {
        $this->buildNamespaceMap();

        $this->discovered = [];
        foreach ($this->namespaces as $namespace => $directory) {
            $this->discovered = array_merge($this->discovered, $this->scanDirectory($directory, $namespace));
        }

    }

    protected function getDescriptions()
    {
        $results = [];
        foreach ($this->discovered as $index => $command) {
            $class = $command['namespace'].'\\'.$command['className'];

            if(!class_exists($class)){
                throw new ConsoleException(sprintf('%s does not exist or cannot be found',$class));
            }
            $object = new $class();
            $name = $object->name();
            $description = $object->description();

            list($ns, $cmd) = commandSplit($name);
            $results[$ns][$name] = $object->description();
        }

        return $results;
    }

    /**
     * This the workhorse, runs the command, displays help.
     *
     * @param array     $args
     */
    public function run(array $args)
    {
        array_shift($args); // first arg is the script that called it
        if (empty($args)) {
            $this->displayHelp();
            return;
        }
  
        $this->command = $this->findCommand($args[0]);
       
        if ($this->command) {
            array_shift($args);
            try {
                $this->command->run($args);
                return true;
            } catch (StopExecutionException $ex) {
                return false;
            }
        } else {
            $this->io->error("Command `{$args[0]}` not found"); // Original
        }

        return false;
    }

    /**
     * Returns the Command object that was created
     *
     * @return \Origin\Command\Command
     */
    public function command(){
        return $this->command;
    }

    /**
     * This will find the command, prioritizing main name space with conventions, if not
     * it will do autodiscovery.
     *
     * @param string $command
     *
     * @return \Origin\Command\Command
     */
    public function findCommand(string $command)
    {
        $namespace = Configure::read('App.namespace');
        $className = $namespace.'\\Command\\'.Inflector::camelize(preg_replace('/[:-]/', '_', $command)).'Command';


        if(!class_exists($className)){
            throw new ConsoleException(sprintf('%s does not exist or cannot be found',$className));
        }

        $object = new $className($this->io);
        if ($object->name() === $command) {
            return $object;
        }

        $this->autoDiscover();
        $commands = $this->getCommandList();
     
        if (isset($commands[$command])) {
            $className = $commands[$command];

            return new $className($this->io);
        }

        return null;
    }

    protected function getCommandList()
    {
        $results = [];
        foreach ($this->discovered as $command) {
            
            $class = $command['namespace'].'\\'.$command['className'];
            if(class_exists($class)){
                $object = new $class();
                $results[$object->name()] = $class;
            }
          
        }

        return $results;
    }

    protected function displayHelp()
    {
        $this->autoDiscover();
        $commands = $this->getDescriptions();

        $out = [];
        $out[] = '<text>OriginPHP</text>';
        $out[] = '';
        $out[] = '<heading>Usage:</heading>';
        $out[] = '  <text>console <command> [options] [arguments]</text>';
        $out[] = '';

        $maxLength = 0;
        foreach ($commands as $group => $cmds) {
            foreach ($cmds as $cmd => $description) {
                if (strlen($cmd) > $maxLength) {
                    $maxLength = strlen($cmd);
                }
            }
        }

        ksort($commands);
        foreach ($commands as $group => $cmds) {
            if($group){
                $out[] = '<heading>'.$group.'</heading>';
            }
          
            foreach ($cmds as $cmd => $description) {
                $cmd = str_pad($cmd, $maxLength + 2, ' ', STR_PAD_RIGHT);
                $out[] = "<code>{$cmd}</code><text>{$description}</text>";
            }
            $out[] = '';
        }
        $this->io->out($out);
    }

    /**
     * Scans directory building up meta information for commands.
     *
     * @param string $directory
     * @param string $namespace
     */
    public function scanDirectory(string $directory, string $namespace)
    {
        $results = [];
        if (!file_exists($directory)) {
            return [];
        }
        $files = scandir($directory);
       
        foreach ($files as $file) {
            if (substr($file, -4) !== '.php') {
                continue;
            }
            if (substr($file, -11) === 'Command.php' and $file !== 'Command.php') {
                $results[] = [
                    'className' => substr($file, 0, -4),
                    'namespace' => $namespace.'\\Command',
                    'filename' => $directory.DS.$file,
                ];
            }
        }

        return $results;
    }
}
