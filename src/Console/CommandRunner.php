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

namespace Origin\Console;

use Origin\Core\Plugin;
use Origin\Core\Configure;
use Origin\Core\Inflector;
use Origin\Console\Exception\StopExecutionException;

class CommandRunner
{
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

            $object = new $class();
            $name = $object->name();
            $description = $object->description();

            list($ns, $cmd) = commandSplit($name);
            $results[$ns][$name] = $object->description();
        }

        return $results;
    }

    public function run(array $args, ConsoleIo $io = null)
    {
        if ($io === null) {
            $io = new ConsoleIo();
        }

        array_shift($args);
        if (empty($args)) {
            $this->displayHelp($io);

            return;
        }

        $className = $this->findCommand($args[0]);

        if ($className) {
            array_shift($args);
            try {
                $command = new $className($io);
                $command->run($args);
            } catch (StopExecutionException $ex) {
                return false;
            }
        } else {
            $io->error("Command {$args[0]} not found");
        }

        return false;
    }

    /**
     * This will find the command, prioritizing main name space with conventions, if not 
     * it will do autodiscovery.
     *
     * @param string $command
     * @return void
     */
    protected function findCommand(string $command){
        
        $namespace = Configure::read('App.namespace');
        $className = $namespace . '\\' . Inflector::camelize(preg_replace('/[:-]/','_',$command)) . 'Command';
        if(class_exists($className)){
            $object = new $className();
            if($object->name() === $command){
                return $className;
            }
        }

        $this->autoDiscover();
        $commands = $this->getCommandList();
        if(isset($commands[$command])){
            return $commands[$command];
        }
        return null;
    }

    protected function getCommandList()
    {
        $results = [];
        foreach ($this->discovered as $command) {
            $class = $command['namespace'].'\\'.$command['className'];
            $object = new $class();
            $results[$object->name()] = $class;
        }

        return $results;
    }

    protected function displayHelp(ConsoleIo $io)
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

        foreach ($commands as $group => $cmds) {
            foreach ($cmds as $cmd => $description) {
                $cmd = str_pad($cmd, $maxLength + 2, ' ', STR_PAD_RIGHT);
                $out[] = "<code>{$cmd}</code><text>{$description}</text>";
            }
            $out[] = '';
        }
        $io->out($out);
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
