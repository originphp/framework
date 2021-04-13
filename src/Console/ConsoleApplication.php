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

use Origin\Core\Resolver;
use Origin\Core\HookTrait;
use Origin\Core\LazyLoadContainer;
use Origin\Console\Command\Command;
use Origin\Core\Exception\Exception;
use Origin\Console\Exception\ConsoleException;

/**
 * If you only add one command, by default it will become a single command application and the command
 * will be run automatically.
 *
 * @example:
 * #!/usr/bin/env php
 * require __DIR__ . '/vendor/originphp/framework/src/Core/bootstrap.php';
 * use Origin\Console\ConsoleApplication;
 *
 * $consoleApplication = new ConsoleApplication();
 * $consoleApplication->name('db');
 * $consoleApplication->description([
 *  'DB application for creating and dropping tables'
 * ]);
 * $consoleApplication->addCommand('create', 'CreateTable');
 * exit($consoleApplication->run());
 */

use Origin\Core\Exception\InvalidArgumentException;
use Origin\Console\Exception\StopExecutionException;

class ConsoleApplication
{
    use HookTrait;
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
    protected $description = null;

    /**
    * Holds the command list
    *
    * @var array
    */
    protected $commands = [];

    /**
     * Holds the Command Registry
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
     * Undocumented variable
     *
     * @var \Origin\Console\ArgumentParser
     */
    protected $argumentParser = null;

    /**
     * Constructor
     *
     * @param string $name should be the same as the executable file as this will be used in help
     */
    public function __construct(ConsoleIo $io = null)
    {
        $this->io = $io ?: new ConsoleIo();

        $this->argumentParser = new ArgumentParser();

        $this->argumentParser->addOption('help', [
            'short' => 'h','description' => 'Displays this help message','type' => 'boolean',
        ]);
   
        $this->executeHook('initialize');
        
        $this->commandRegistry = new LazyLoadContainer();
    }

    /**
    * Sets the name for the application
    *
    * @param string $name
    * @return string|null
    */
    public function name(string $name = null)
    {
        if ($name === null) {
            return $this->name;
        }
        if (! preg_match('/^[a-z-]+$/', $name)) {
            throw new ConsoleException(sprintf('Command App name `%s` is invalid', $name));
        }
        $this->name = $name;
    }

    /**
     * Sets the description
     *
     * @param string|array $description
     * @return string|null
     */
    public function description($description = null)
    {
        if ($description === null) {
            return $this->description;
        }
        if (is_array($description)) {
            $description = implode("\n", $description);
        }
        $this->description = $description;
    }

    /**
     * Runs the console application
     *
     * @param array $args default is argv
     * @return int exit code
     */
    public function run(array $args = null): int
    {
        if ($args === null) {
            global $argv;
            $args = $argv;
            array_shift($args); // Remove script that is running
        }

        $commands = $this->commandRegistry->list();
        if (empty($commands)) {
            throw new ConsoleException('No commands have been added to this application.');
        }

        // Detect and extract Command
        $command = $commandName = null;
        if (count($commands) === 1) {
            $command = $commands[0]; # If its one command application load the first one by default
        }
        foreach ($args as $i => $arg) {
            if ($command === null and substr($arg, 0, 1) !== '-') {
                $command = $arg;
                unset($args[$i]);
                break;
            }
        }
        
        if (! $command) {
            $this->displayHelp();

            return Command::SUCCESS;
        }

        try {
            $this->$command = $this->commandRegistry->get($command);
        } catch (Exception $ex) {
            $this->io->error("Invalid command {$command}.");

            return Command::ERROR;
        }
      
        $commandName = (count($commands) === 1) ? $this->name : $this->name . ' ' .$command;
        
        # Configure Command
        $this->$command->io($this->io);
        $this->$command->name($commandName);  // Rename for help

        try {
            $this->executeHook('startup');
            $exitCode = $this->$command->run($args);
            $this->executeHook('shutdown');

            return $exitCode;
        } catch (StopExecutionException $ex) {
            return $ex->getCode();
        }
    }

    /**
     * Displays the help for this app
     *
     * @return void
     */
    public function displayHelp(): void
    {
        $formatter = new ConsoleHelpFormatter();
      
        if ($this->description) {
            $formatter->setDescription($this->description);
        }
        $formatter->setUsage(["{$this->name} command [options] [arguments]"]);
        $commands = [];
        
        $list = $this->commandRegistry->list();
        sort($list);

        foreach ($list as $name) {
            $command = $this->commandRegistry->get($name);
            $commands[$name] = $command->description();
        }
        $formatter->setCommands($commands);
        $this->io->out($formatter->generate());
    }
    
    /**
     * Adds a command to this Console Application
     *
     * @param string $alias
     * @param string $name Cache,Plugin.Cache, App\Console\Command\Custom\Cache
     * @return void
     */
    public function addCommand(string $alias, string $name): void
    {
        if (! preg_match('/^[a-z0-9-]+$/', $alias)) {
            throw new ConsoleException(sprintf('Alias `%s` is invalid', $alias));
        }

        $className = Resolver::className($name, 'Command', 'Command', 'Console');
        if (! $className) {
            throw new InvalidArgumentException(sprintf('`%s` command not found.', $name));
        }
        $this->commandRegistry->add($alias, $className);
    }
}
