<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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

use Origin\Console\Exception\ConsoleException;

class ArgumentParser
{
    protected $options = [];

    protected $shortOptions = [];

    protected $arguments = [];

    protected $commands = [];

    /**
     * Command Name
     *
     * @var string
     */
    protected $command = null;

    /**
     * Description displayed before help
     *
     * @var string
     */
    protected $description = null;

    /**
     * Text displayed after help
     *
     * @var string
     */
    protected $epilog = null;

    /**
     * Additional Long Help text
     *
     * @var string
     */
    protected $help = null;

    protected $usage = null;

    public function __construct(string $name = 'command', string $description = null)
    {
        $this->command = $name;
        $this->description = $description;
    }
    /**
     * Sets the command name
     *
     * @param string $command
     * @return void
     */
    public function setCommand(string $command) : void
    {
        $this->command = $command;
    }

    /**
     * Sets the description
     *
     * @param string|array $description
     * @return void
     */
    public function setDescription($description) : void
    {
        if (is_array($description)) {
            $description = implode("\n", $description);
        }
        $this->description = $description;
    }

    /**
     * Sets the epilog
     *
     * @param string|array $epilog
     * @return void
     */
    public function setEpilog($epilog) : void
    {
        if (is_array($epilog)) {
            $epilog = implode("\n", $epilog);
        }
        $this->epilog = $epilog;
    }
    
    /**
     * Sets the help description
     *
     * @param string|array $help
     * @return void
     */
    public function setHelp($help) : void
    {
        if (is_array($help)) {
            $help = implode("\n", $help);
        }
        $this->help = $help;
    }

    /**
    * Sets the usage
    *
    * @param string|array $usage
    * @return void
    */
    public function setUsage($usage) : void
    {
        if (is_array($usage)) {
            $usage = implode("\n", $usage);
        }
        $this->usage = $usage;
    }

    /**
     * Adds an option
     *
     * @param string $name
     * @param array $options
     *  - description: help text
     *  - short: the short command, this is with single -. e.g -f
     *  - default: null
     *  - required: default false
     *  - type: string, integer, boolean
     *  - banner: for displayHelp. default is uppercase value e.g --connection=DATASOURCE
     * @return void
     */
    public function addOption(string $name, array $options = []) : void
    {
        $options += ['name' => $name,'short' => null,'default' => null,'required' => false,'type' => 'string','description' => '','banner' => strtoupper($name)];
        if ($options['default'] && $options['required']) {
            throw new ConsoleException("Option {$name} cannot be required and have default value");
        }
        if (! in_array($options['type'], ['string','boolean','integer','array','hash'])) {
            throw new ConsoleException("Option {$name} invalid type");
        }
    
        if ($options['short']) {
            $this->shortOptions[$options['short']] = $options;
        }
        $this->options[$name] = $options;
    }

    /**
     * Adds command
     *
     * @param string $name
     * @param array $options
     * @return void
     */
    public function addCommand(string $name, array $options = []) : void
    {
        $options += ['name' => $name,'description' => null];
        $this->commands[$name] = $options;
    }

    /**
     * Adds an argument
     *
     * @param string $name
     * @param array $options
     *  - description: help text
     *  - required: default false
     *  - type: string, integer, boolean, array hash
     * @return void
     */
    public function addArgument(string $name, array $options = []) : void
    {
        $options += ['name' => $name,'default' => null,'required' => false,'type' => 'string','description' => ''];
        if ($options['required'] && $this->arguments) {
            $arg = end($this->arguments);
            if ($arg['required'] === false) {
                throw new ConsoleException('You cannot add a required argument after an optional one.');
            }
        }
        if ($this->arguments) {
            $arg = end($this->arguments);
            if ($arg['type'] === 'array' || $arg['type'] === 'hash') {
                throw new ConsoleException('You cannot add an argument after an array or hash argument');
            }
        }
        $this->arguments[$name] = $options;
    }

    /**
     * Parses the argv
     *
     * @param array $argv
     * @return array
     */
    public function parse(array $argv) : array
    {
        $arguments = $options = [];
        $args = [];
        foreach ($argv as $key => $arg) {
            if (is_string($arg) && $this->isLongOption($arg)) {
                $options = $this->parseLongOption($arg, $options);
            } elseif (is_string($arg) && $this->isShortOption($arg)) {
                $options = $this->parseShortOption($arg, $options);
            } else {
                $args[] = $arg;
            }
        }

        # Process Args
        $arguments = $this->parseArguments($args);

        foreach ($this->options as $option) {
            if (! empty($option['required']) && empty($options[$option['name']])) {
                throw new ConsoleException(sprintf('Missing required option `%s`', $option['name']));
            }
            if ($option['type'] === 'boolean' && ! isset($options[$option['name']])) {
                $options[$option['name']] = false;
            } elseif (! empty($option['default']) && ! isset($options[$option['name']])) {
                $options[$option['name']] = $option['default'];
            }
        }

        foreach ($this->arguments as $argument) {
            if (! empty($options['help'])) {
                break;
            }
            if (! empty($argument['required']) && ! isset($arguments[$argument['name']])) {
                throw new ConsoleException(sprintf('Missing required argument `%s`', $argument['name']));
            }
        }

        return [$options,$arguments];
    }

    /**
     * Parses the arguments
     *
     * @param array $args extracted args
     * @return array
     */
    protected function parseArguments(array $args) : array
    {
        $keys = array_keys($this->arguments);
        $arguments = [];
        foreach ($args as $key => $arg) {
            if (isset($keys[$key])) {
                $name = $keys[$key];
                $type = $this->arguments[$name]['type'];
                $max = count($args);
                if ($type === 'array') {
                    for ($i = $key;$i < $max;$i++) {
                        $values[] = $args[$i];
                    }
                    $arguments[$name] = $values;
                    break;
                } elseif ($type === 'hash') {
                    for ($i = $key;$i < $max;$i++) {
                        if (strpos($args[$i], ':') !== false) {
                            list($k, $v) = explode(':', $args[$i]);
                            $values[$k] = $v;
                        } else {
                            $values[] = $args[$i];
                        }
                    }
                    $arguments[$name] = $values;
                    break;
                }
                $arguments[$name] = $this->value($type, $arg);
            }
        }

        return $arguments;
    }

    /**
     * Converst a value
     *
     * @param string $type
     * @param mixed $value
     * @return mixed
     */
    protected function value(string $type, $value)
    {
        if ($type === 'boolean') {
            return (bool) $value;
        }
        
        if ($type === 'integer') {
            return (int) $value;
        }

        if ($type === 'string') {
            return (string) $value;
        }

        return $value;
    }
    /**
     * Parse an option
     *
     * @param string $option
     * @param array $options
     * @return array
     */
    protected function parseOption(string $option, array $options) : array
    {
        $name = $this->getOptionName($option);
        if ($this->options[$name]['type'] === 'boolean') {
            $options[$name] = true;

            return $options;
        }
 
        $value = $this->options[$name]['default'];
       
        if (strpos($option, '=') !== false) {
            list($option, $value) = explode('=', $option);
        }
      
        $value = $this->value($this->options[$name]['type'], $value);
 
        $options[$name] = $value;
   
        return $options;
    }

    /**
     * Parses a long option e.g. --connection=1234
     *
     * @param string|int $arg
     * @param array $options
     * @return array
     */
    protected function parseLongOption($arg, array $options) : array
    {
        $option = substr($arg, 2);
        $name = $this->getOptionName($option);
        if (! isset($this->options[$name])) {
            throw new ConsoleException(sprintf('Unkown option --%s', $name));
        }

        return $this->parseOption($option, $options);
    }

    /**
     * Parses a short option e.g. -ds=1234
     *
     * @param int|string $arg
     * @param array $options
     * @return array
     */
    protected function parseShortOption($arg, array $options) : array
    {
        $option = substr($arg, 1);
        $name = $this->getOptionName($option);
   
        if (! isset($this->shortOptions[$name])) {
            throw new ConsoleException(sprintf('Unkown short option -%s', $name));
        }
        $option = $this->shortOptions[$name]['name'];
        if (strpos($arg, '=') !== false) {
            list($k, $v) = explode('=', $arg);
            $option .= "={$v}";
        }

        return $this->parseOption($option, $options);
    }

    /**
     * Parses the option string to get a name
     *
     * @param string $option
     * @return string
     */
    protected function getOptionName(string $option) : string
    {
        if (strpos($option, '=') !== false) {
            list($option, $value) = explode('=', $option);
        }

        return $option;
    }

    /**
     * Checks if an option is a long option
     *
     * @param string $option
     * @return boolean
     */
    protected function isLongOption(string $option) : bool
    {
        return (substr($option, 0, 2) === '--');
    }

    /**
        * Checks if an option is a short option
        *
        * @param string $option
        * @return boolean
        */
    protected function isShortOption(string $option) : bool
    {
        return ($option[0] === '-' and substr($option, 0, 2) != '--');
    }

    /**
     *
     * Generates the usage only
     * @param string $name
     * @return string
     */
    public function usage() : string
    {
        $formatter = new ConsoleHelpFormatter();
        $formatter->setUsage($this->generateUsage($this->command));

        return $formatter->generate();
    }

    /**
     * Generates the help
     *
     * @return string
     */
    public function help() : string
    {
        $formatter = new ConsoleHelpFormatter();
       
        if ($this->description) {
            $formatter->setDescription($this->description);
        }

        $usages = $this->generateUsage($this->command);
       
        if ($this->usage) {
            $usages = $usages ."\n" . $this->usage;
        }
        $formatter->setUsage($usages);
       
        $formatter->setArguments($this->generateArguments());
        $formatter->setOptions($this->generateOptions());
        $formatter->setCommands($this->generateCommands());
        if ($this->epilog) {
            $formatter->setEpilog($this->epilog);
        }
        if ($this->help) {
            $formatter->setHelp($this->help);
        }

        return $formatter->generate();
    }

    /**
     * Generates the arguments for help
     *
     * @return array
     */
    protected function generateArguments() : array
    {
        $arguments = [];
        foreach ($this->arguments as $argument) {
            $description = '';
            if ($argument['description']) {
                $description = $argument['description'];
            }
            $arguments[$argument['name']] = $description;
        }

        return $arguments;
    }

    /**
     * Generates the commands for help
     *
     * @return array
     */
    protected function generateCommands() : array
    {
        $commands = [];
        foreach ($this->commands as $command) {
            $description = '';
            if ($command['description']) {
                $description = $command['description'];
            }
            $commands[$command['name']] = $description;
        }

        return $commands;
    }

    /**
     * Gets the options for help
     *
     * @return array
     */
    protected function generateOptions() : array
    {
        $options = [];
        foreach ($this->options as $option) {
            $text = '--' . $option['name'];

            if ($option['short']) {
                $text = '-' . $option['short']. ', ' . $text;
            }
            if ($option['type'] !== 'boolean') {
                $text .= '=' . $option['banner'] ;
            }
            $help = $option['description'];
            if (array_key_exists('default', $option) && $option['default'] !== null) {
                $default = " <yellow>[default: {$option['default']}]</yellow>";
                if (is_array($help)) {
                    $rows = count($help);
                    $help[$rows - 1] .= $default;
                } else {
                    $help .= $default;
                }
            }
            $options[$text] = $help;
        }

        return $options;
    }

    /**
     * Generats the usage string
     *
     * @param string $command
     * @return string
     */
    protected function generateUsage(string $command = 'command') : string
    {
        $options = $arguments = [];
        foreach ($this->options as $option) {
            if (! empty($option['required'])) {
                $options[] = '--'.  $option['name'];
            }
        }
     
        $options[] = '[options]';
        
        foreach ($this->arguments as $arg) {
            if (! empty($arg['required'])) {
                $arguments[] = $arg['name'];
            } else {
                $arguments[] = "[{$arg['name']}]";
            }
        }
        // Dont duplicate
        if (empty($arguments)) {
            $arguments[] = '[arguments]';
        }
       
        if (! empty($this->commands)) {
            $command .= ' command';
        }
    
        return $command . ' ' .  implode(' ', array_merge($options, $arguments));
    }
}
