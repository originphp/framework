<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Console;

use Origin\Core\Config;
use Origin\Core\Plugin;
use Origin\Inflector\Inflector;
use Origin\Console\Command\Command;
use Origin\Console\Exception\ConsoleException;
use Origin\Console\Exception\StopExecutionException;

class CommandRunner
{

    /**
     * Holds the Command from RUN
     *
     * @var \Origin\Console\Command\Command
     */
    protected $command;

    /**
     * @var array
     */
    protected $commands = [];

    /**
     * Holds a list of namespaces in array ['namespace'=>'path'].
     * e.g [Origin => /var/www/vendor/originphp/framework/src/Command]
     *
     * @var array
     */
    protected $namespaces = [];

    /**
     * Console IO object
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
     *
     * @return void
     */
    protected function buildNamespaceMap(): void
    {
        $this->namespaces = [
            Config::read('App.namespace') => APP . '/Console/Command'
        ];

        $plugins = Plugin::loaded();
        foreach ($plugins as $plugin) {
            $this->namespaces[$plugin] = Plugin::path($plugin). '/src/Console/Command';
        }
    }

    /**
     * Goes through discovery process.
     *
     * @return void
     */
    protected function autoDiscover(): void
    {
        $this->buildNamespaceMap();

        $this->discovered = [];
        foreach ($this->namespaces as $namespace => $directory) {
            $this->discovered = array_merge($this->discovered, $this->scanDirectory($directory, $namespace));
        }
    }

    /**
     * Gets the list of descriptions of the commands
     *
     * @return array
     */
    protected function getDescriptions(): array
    {
        $results = [];
        foreach ($this->discovered as $index => $command) {
            $class = $command['namespace'].'\\'.$command['className'];
            if (! class_exists($class)) {
                throw new ConsoleException(sprintf('%s does not exist or cannot be found', $class));
            }
            $object = $this->createCommand($class);
            $name = $object->name();
  
            list($ns, $cmd) = commandSplit($name);
            $results[$ns][$name] = $object->description();
        }

        return $results;
    }

    /**
     * This the workhorse, runs the command, displays help.
     *
     * @param array $args
     * @return integer exit code
     */
    public function run(array $args): int
    {
        array_shift($args); // first arg is the script that called it
        if (empty($args)) {
            $this->displayHelp();

            return Command::SUCCESS;
        }
  
        $this->command = $this->findCommand($args[0]);
       
        if ($this->command) {
            array_shift($args);
            try {
                return $this->command->run($args);
            } catch (StopExecutionException $ex) {
                return $ex->getCode();
            }
        } else {
            $this->io->error("Command `{$args[0]}` not found"); // Original
        }

        return Command::ERROR;
    }

    /**
     * Returns the Command object that was created
     *
     * @return \Origin\Console\Command\Command|null
     */
    public function command(): ?Command
    {
        return $this->command;
    }

    /**
     * This will find the command, prioritizing main name space with conventions, if not
     * it will do autodiscovery.
     *
     * @param string $command
     *
     * @return \Origin\Console\Command\Command|null
     */
    public function findCommand(string $command): ?Command
    {
        # Use Conventions - Faster
        $namespace = Config::read('App.namespace');
        $className = $namespace.'\Console\Command\\'.Inflector::studlyCaps(preg_replace('/[:-]/', '_', $command)).'Command';
       
        $object = $this->createCommand($className);
        if ($object && $object->name() === $command) {
            return $object;
        }
       
        $this->autoDiscover();
        $commands = $this->getCommandList();
     
        $object = null;
        if (isset($commands[$command])) {
            $object = $this->createCommand($commands[$command]);
        }

        return $object;
    }

    /**
     * Returns the list of commands
     *
     * @return array
     */
    protected function getCommandList(): array
    {
        $results = [];

        foreach ($this->discovered as $command) {
            $class = $command['namespace'].'\\'.$command['className'];
            if (class_exists($class)) {
                $object = $this->createCommand($class);
                $results[$object->name()] = $class;
            }
        }

        return $results;
    }

    /**
     * Displays help to screen
     *
     * @return void
     */
    protected function displayHelp(): void
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
            if ($group) {
                $out[] = '<heading>'.$group.'</heading>';
            }
          
            foreach ($cmds as $cmd => $description) {
                if (! is_array($description)) {
                    $description = [$description];
                }
                foreach ($description as $desc) {
                    $cmd = str_pad($cmd, $maxLength + 2, ' ', STR_PAD_RIGHT);
                    $out[] = "<code>{$cmd}</code><text>{$desc}</text>";
                    $cmd = null;
                }
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
     * @return array
     */
    public function scanDirectory(string $directory, string $namespace): array
    {
        $results = [];

        if (! file_exists($directory)) {
            return [];
        }
       
        $files = scandir($directory);
       
        foreach ($files as $file) {
            if (substr($file, -4) !== '.php') {
                continue;
            }
            if (substr($file, -11) === 'Command.php' && $file !== 'Command.php') {
                $results[] = [
                    'className' => substr($file, 0, -4),
                    'namespace' => $namespace.'\Console\Command',
                    'filename' => $directory . '/' . $file,
                ];
            }
        }

        return $results;
    }

    /**
     * Creates a Command object and catches warnings
     *
     * @param string $class
     * @return \Origin\Console\Command|null
     */
    protected function createCommand(string $class): ?Command
    {
        $object = null;
        if (class_exists($class)) {
            try {
                $object = new $class($this->io);
            } catch (\ErrorException $exception) {
                // there is an issue in the command
                throw new ConsoleException(sprintf('%s in %s', $exception->getMessage(), $class));
            }

            return $object;
        }

        return null;
    }
}
