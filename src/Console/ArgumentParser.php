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

 /**
  * @todo implment array and hashes
  */
namespace Origin\Console;
use Origin\Console\Exception\ConsoleException;
use Origin\Console\ConsoleHelpFormatter;

class ArgumentParser
{
    protected $options = [];

    protected $shortOptions = [];

    protected $arguments = [];
    /**
     * Undocumented function
     *
     * @param string $name
     * @param array $options
     *  - short: the short command, this is with single -. e.g -f
     *  - default: null
     *  - required: default false
     *  - type: string, numeric, boolean, array hash
     * @return void
     */
    public function addOption(string $name,array $options=[]){
        $options += ['name'=>$name,'short'=>null,'default'=>null,'required'=>false,'type'=>'string','description'=>''];
        if($options['default'] AND $options['required']){
            throw new ConsoleException("Option {$name} cannot be required and have default value");
        }
        if(!in_array($options['type'],['string','boolean','integer','array','hash'])){
            throw new ConsoleException("Option {$name} invalid type");
        }
        
        if($options['short']){
            $this->shortOptions[$options['short']] = $options;
        }
        $this->options[$name] = $options;
    }

    public function addArgument(string $name,array $options=[]){
        $options += ['name'=>$name,'default'=>null,'required'=>false,'type'=>'string','description'=>''];
        if($options['required'] AND $this->arguments){
            $arg = end($this->arguments);
            if($arg['required'] === false){
                throw new ConsoleException("You cannot add a required argument after an optional one.");
            }
        }    
        if($this->arguments){
            $arg = end($this->arguments);
            if( $arg['type'] === 'array' OR $arg['type'] ==='hash'){
                throw new ConsoleException("You cannot add an argument after an array or hash argument");
            }
        } 
        $this->arguments[$name] = $options;
    }

    public function parse(array $argv){
        $arguments = $options = [];
        $args = [];
        foreach($argv as $key => $arg){
            if($this->isLongOption($arg)){
                $options = $this->parseLongOption($arg,$options);
            }
            elseif($this->isShortOption($arg)){
                $options = $this->parseShortOption($arg,$options);
            }
            else{
                $args[] = $arg;
            }
        }

        # Process Args 
        $arguments = $this->parseArguments($args,$argv);

        foreach($this->options as $option){
            if(!empty($option['required']) AND empty($options[$option['name']])){
                throw new ConsoleException(sprintf('Missing required option `%s`',$option['name']));
            }
            if(!empty($option['default']) AND !isset($options[$option['name']])){
                $options[$option['name']] = $option['default'];
            }
        }

        $requiredArguments = [];
        foreach($this->arguments as $argument){
            if(!empty($options['help'])){
                break;
            }
            if(!empty($argument['required']) AND !isset($arguments[$argument['name']])){
                throw new ConsoleException(sprintf('Missing required argument `%s`',$argument['name']));
            }
        }
        return [$options,$arguments];
    }   

    /**
     * Undocumented function
     *
     * @param array $args extracted args
     * @param array $argv argv array 
     * @return void
     */
    protected function parseArguments(array $args,array $argv){
        $keys = array_keys($this->arguments);
        $arguments = [];
        foreach($args as $key => $arg){
            if(isset($keys[$key])){
                $name = $keys[$key];
                $type = $this->arguments[$name]['type'];
                $values = [];
                if($type === 'array'){
                    for($i=$key;$i<count($argv);$i++){
                        $values[] = $argv[$i];
                    }
                    $arguments[$name] = $values; 
                    break;
                }
                elseif($type ==='hash'){
                    for($i=$key;$i<count($argv);$i++){
                        if(strpos($argv[$i],':') !== false){
                            list($k,$v) = explode(':',$argv[$i]);
                            $values[$k] = $v;
                        }
                        else{
                            $values[] = $argv[$i];
                        } 
                    }
                    $arguments[$name] = $values; 
                    break;
                }
                $arguments[$name] = $this->value($type,$arg);
            }
        }
        return $arguments;
    }

    protected function value($type,$value){
        if($type ==='boolean'){
            return (bool) $value;
        }
        if($type ==='integer'){
            return (int) $value;
        }
        return $value;
    }

    protected function parseOption($option,$options){
      
        $name = $this->getOptionName($option);
        if($this->options[$name]['type'] === 'boolean'){
            $options[$name] = true;
            return $options;
        }
 
        $value = $this->options[$name]['default'];
       
        if (strpos($option, '=') !== false) {
            list($option, $value) = explode('=', $option);
        }
      
        if($this->options[$name]['type'] === 'boolean'){
            $value = (bool) $value;
        }
        elseif($this->options[$name]['type'] === 'numeric'){
            $value = (int) $value;
        }


        $options[$name] = $value;
   
        return $options;
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
        if(strpos($arg,'=') !== false){
            list($k,$v) = explode('=',$arg);
            $option .= "={$v}";
        }
        return $this->parseOption($option,$options);
    }

    protected function getOptionName($option){
        if(strpos($option,'=') !== false){
            list($option,$value) = explode('=',$option);
        }
        return $option;
    }

    protected function isLongOption(string $option){
        return (substr($option,0,2) === '--');
    }

    protected function isShortOption(string $option){
        return ($option[0] === '-' AND substr($option,0,2) != '--');
    }

    protected function formatDescription(string $left,$right){
        $out = [];
        $left = '  ' . $left;
        foreach((array) $right as $row){
            $row = "<text>{$row}</text>";
            if(empty($out)){
                $out[] = '<code>' .$left . '</code>' . $row;
            }
            else{
                $out[] =  str_repeat(' ',strlen($left)) .  $row;
            }
           
        }
        return $out;
    }

    public function help(string $name,$description=null){
        $formatter = new ConsoleHelpFormatter();
        if($description){
            $formatter->setDescription($description);
        }
        $formatter->setUsage($this->generateUsage($name));
        $formatter->setArguments($this->generateArguments());
        $formatter->setOptions($this->generateOptions());
        return $formatter->generate();
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

    protected function generateArguments(){
        $arguments = [];
        foreach($this->arguments as $argument){
            $description = '';
            if($argument['description']){
                $description = $argument['description'];
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
    protected function generateOptions(){
        $options = [];
        foreach ($this->options as $option) {
   
            $text = '--' . $option['name'];

            if ($option['short']) {
                $text = '-' . $option['short']. ', ' . $text; 
            }
            if($option['type']  !== 'boolean'){
                $text .=  '=' . strtoupper($option['name']) ;
            }
            $help = $option['description'];
            if(!empty($option['default'])){
              $default = " \033[93m(default: {$option['default']})\033[0m"; //  Append this without breaking color/multi line
                if(is_array($help)){
                    $rows = count($help);
                    $help[$rows-1] .= $default;
                }
                else{
                    $help .= $default;
                }
             
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
     
        $options[] = '[options]';
        
        foreach($this->arguments as $arg){
            if(!empty($arg['required'])){
                $arguments[] = $arg['name'];
            }
            else{
                $arguments[] = "[{$arg['name']}]";
            }
        }
        // Dont duplicate
        if(empty($arguments)){
            $arguments[] = '[arguments]';
        }
       
     
        return '  ' . $command . ' ' .  implode(' ',array_merge($options,$arguments));
    }

}