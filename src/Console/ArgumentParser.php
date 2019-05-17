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
use Origin\Console\Exception\ConsoleException;
class ArgumentParser
{
    /**
     * Command argument configuration
     *
     * @var array
     */
    protected $arguments = [];
   /**
     * Command options configuration
     *
     * @var array
     */
    protected $options = [];

    

    protected $shortOptions = [];

    protected $name = null;

    public function __construct(string $name, array $arguments,array $options){
        $this->arguments = $arguments;
        $this->options = $options;

        // Create the short options
        foreach ($options as $option) {
            if ($option['short']) {
                $this->shortOptions[$option['short']] = $option;
            }
        }        

        $lastArgument = null;
        foreach($arguments as $argument){
            if($lastArgument === null){
                $lastArgument = $argument;
                continue;
            }
            if($argument['required'] === true AND $lastArgument['required'] === false){
                throw new ConsoleException('You cannot add a required argument after an optional one.');
            }
            $lastArgument = $argument;
        }
    }

    public function parse(array $argv){
        $options = $arguments = [];

        $keys = array_keys($this->arguments);

        foreach($argv as $arg){
            if($this->isLongOption($arg)){
                $options = $this->parseLongOption($arg,$options);
            }
            elseif($this->isShortOption($arg)){
                $options = $this->parseShortOption($arg,$options);
            }
            else{
                $next = count($arguments);
                if(!isset($keys[$next])){
                    throw new ConsoleException('Too many arguments');
                }
                $arguments[$keys[$next]] = $arg;
            }
        }
        $help = isset($options['help']);
        foreach($this->options as $option){
            if(!empty($option['required']) AND empty($options[$option['name']]) AND !$help){
                throw new ConsoleException(sprintf('Missing required option `%s`',$option['name']));
            }
            if(!empty($option['default']) AND !isset($options[$option['name']])){
                $options[$option['name']] = $option['default'];
            }
        }

        $requiredArguments = [];
        foreach($this->arguments as $argument){
            if(!empty($argument['required']) AND !isset($arguments[$argument['name']]) AND !$help){
                throw new ConsoleException(sprintf('Missing required argument `%s`',$argument['name']));
            }
            elseif(!empty($argument['choices']) AND isset($arguments[$argument['name']]) AND !in_array($arguments[$argument['name']],$argument['choices'])){
                throw new ConsoleException(sprintf('Argument `%s` is not allowed',$argument['name']));
            }
        }
        
        return [$options,$arguments];
    }

    public function help(string $name,$description = null){
        $out = [];
        if($description){
            $out[] = $description;
            $out[] = '';
        }
        $out[] = "<heading>Usage:</heading>";
        $out[] = '<text>'. $this->generateUsage($name).'</text>';
        $out[] = "";

        if($this->arguments){
            $out[] = "<heading>Arguments:</heading>";
            $arguments = $this->getArguments();
            $maxLength = $this->getMaxLength($arguments);
            foreach($arguments as $argument => $help){
                $argument = str_pad($argument, $maxLength);
                 $out[]  = "  <code>{$argument}</code>\t{$help}";
            }
            $out[] = "";
        }

        if($this->options){
            $out[] = "<heading>Options:</heading>";
            $options = $this->getOptions();
            $maxLength = $this->getMaxLength($options);
            foreach($options as $option => $help){
                $option = str_pad($option, $maxLength);
                 $out[]  = "  <code>{$option}</code>\t{$help}";
            }
            $out[] = "";
        }

        return implode("\n",$out);

    }

    protected function getMaxLength(array $data){
        $maxLength = 0;
        foreach ($data as $key => $value) {
            if (strlen($key) > $maxLength) {
                $maxLength = strlen($key);
            }
        }
        return $maxLength;
    }

    protected function getArguments(){
        $arguments = [];
        foreach($this->arguments as $argument){
            $description = '';
            if($argument['help']){
                $description = "<text>{$argument['help']}</text> ";
            }
            if($argument['choices']){
                $description .= '<warning>[' .implode(', ',$argument['choices']) . ']</warning>';
            }
            $arguments[$argument['name']] = $description;
           
        }
        return $arguments;
    }

    /**
     * Gets the options for help
     *
     * @return array
     */
    protected function getOptions(){
        $options = [];
        foreach ($this->options as $option) {
   
            $text = '--' . $option['name'];

            if ($option['short']) {
                $text = '-' . $option['short']. ', ' . $text; 
            }
            if($option['boolean'] === false){
                $text .=  '=' . strtoupper($option['name']) ;
            }
            $help = "<text>{$option['help']}</text>";
            if(!empty($option['default'])){
                $help .= " <warning>(default: {$option['default']})</warning>";
            }

            $options[$text] =  $help;
        }
        return $options;
    }

    protected function generateUsage(string $command ='command'){
        $results = [];
     
        $options = $arguments = [];
        foreach($this->options as $option){
            if(!empty($option['required'])){
                $options[] = '--'.  $option['name'];
            }
        }

        foreach($this->arguments as $arg){
            if(!empty($arg['required'])){
                $arguments[] = $arg['name'];
            }
            else{
                $arguments[] = "[{$arg['name']}]";
            }
        }

        if(empty($arguments)){
            $arguments = ['[arguments]'];
        }
       
    
        return '  ' . $command . ' ' .  implode(' ',array_merge($options,$arguments));
    }

    protected function parseLongOption($arg,$options){
        $option = substr($arg,2);
        $name = $this->getOptionName($option);
    
        if(!isset($this->options[$name])){
            throw new ConsoleException(sprintf('Unkown option --%s', $name));
        }
        return $this->parseOption($option,$options);
    }

    protected function parseShortOption($arg,$options){
        $option = substr($arg,1);
        $name = $this->getOptionName($option);
        if(!isset($this->shortOptions[$name])){
            throw new ConsoleException(sprintf('Unkown short option -%s', $name));
        }
        $option = $this->shortOptions[$name]['name'];
        return $this->parseOption($option,$options);
    }

    protected function getOptionName($option){
        if(strpos($option,'=') !== false){
            list($option,$value) = explode('=',$option);
        }
        return $option;
    }

    protected function parseOption($option,$options){
      
        $name = $this->getOptionName($option);
        if($this->options[$name]['boolean']){
            $options[$name] = true;
            return $options;
        }
 
        $value = (string) $this->options[$name]['default'];
        if (strpos($option, '=') !== false) {
            list($option, $value) = explode('=', $option);
        }
        $options[$name] = $value;
        return $options;
    }

    protected function isLongOption(string $option){
        return (substr($option,0,2) === '--');
    }

    protected function isShortOption(string $option){
        return ($option[0] === '-' AND substr($option,0,2) != '--');
    }
}
