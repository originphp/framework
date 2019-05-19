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

/*
$formatter = new ConsoleHelpFormatter();

$formatter->setDescription([
    'Lorem ipsum dolor sit amet, adipisci nibh risus mollis sagittis, sed egestas erat, dui eu eros,' ,
    'facilisi nulla, wisi aenean id egestas. Ante orci vivamus fusce ac orci eget, id eget tincidunt',
    'nonummy diam.'
]);

$formatter->setUsage(['command [options] [arguments]']);
$formatter->setCommands([
    'app:do-something' => 'Lorem ipsum dolor sit amet, adipisci nibh risus mollis sagittis',
    'app:clear' => 'Ante orci vivamus fusce ac orci eget, id eget tincidunt'
]);

$formatter->setArguments([
    'url' => 'url to access',
    'password' => ['The password to use.','(default: something)']
]);

$formatter->setOptions([
    '-h,--help' => 'Displays this help',
    '-v,--verbose' => 'Displays verbose messaging'
]);

$formatter->setEpilog([
'Lorem ipsum dolor sit amet, adipisci nibh risus mollis sagittis, sed egestas erat, dui eu eros,' ,
'facilisi nulla, wisi aenean id egestas. Ante orci vivamus fusce ac orci eget, id eget tincidunt',
'nonummy diam.'
]);


$help = $formatter->generate();
*/

class ConsoleHelpFormatter
{
    protected $out = [];

    protected $description = '';

    protected $usage = '';

    protected $commands = [];

    protected $arguments = [];

    protected $options = [];

    protected $epilog = '';

    const WIDTH = 72;

    public function generate(){
        $out = [];
        if($this->description){
            $out[] = $this->description;
            $out[] = '';
        }

        if($this->usage){
            $out[] = "<yellow>Usage:</yellow>";
            $out[] = $this->usage;
            $out[] = '';
        }

        $maxWidth = $this->calculateWidth();
        if($this->commands){
            $out[] = "<yellow>Commands:</yellow>";
            $out[] = $this->createTable($this->commands,$maxWidth);
            $out[] = '';
        }

        if($this->arguments){
            $out[] = "<yellow>Arguments:</yellow>";
            $out[] = $this->createTable($this->arguments,$maxWidth);
            $out[] = '';
        }

        if($this->options){
            $out[] = "<yellow>Options:</yellow>";
            $out[] = $this->createTable($this->options,$maxWidth);
            $out[] = '';
        }

        if($this->epilog){
            $out[] = $this->epilog;
            $out[] = '';
        }
       
        return implode("\n",$out);
    }

    protected function calculateWidth(){
        $minWidth = 20;
        foreach([$this->commands,$this->arguments,$this->options] as $table){
            $maxWidth = $this->getMaxWidth($table);
            if($maxWidth > $minWidth){
                $minWidth = $maxWidth;
            }
        }
        return $minWidth + 1;
    }

    /**
     * Adds the description part of help
     *
     * @param [type] $description
     * @return void
     */
    public function setDescription($description)
    {
        if(is_string($description)){
            $description = [$description];
        }
        $this->description = '<white>' . implode("\n",$description) . '</white>';
    }

    public function setUsage($usage){
        if(is_string($usage)){
            $usage = [$usage];
        }
        $usage = implode("\n  ",$usage);
        $this->usage = '<white>' . $this->wrapText($usage,2) .'</white>';
    }

    public function setCommands($commands){
        if(is_string($commands)){
            $commands = [$commands => null];
        }
    
        $this->commands = $commands;
    }

    public function setOptions($options){
        if(is_string($options)){
            $options = [$options];
        }
        $this->options = $options;
    }

    public function setArguments($arguments){
        if(is_string($arguments)){
            $arguments = [$arguments];
        }
        $this->arguments = $arguments;
    }

    public function setEpilog($epilog){
        if(is_string($epilog)){
            $epilog = [$epilog];
        }
        $this->epilog = '<white>' . implode("\n",$epilog) . '</white>';
    }

    /**
     * Pads columns for a table
     *
     * @param array $array
     * @return void
     */
    protected function createTable(array $array,int $width = 20){
        $out = [];
        foreach($array as $left => $right){
           
            $left = str_pad($left,$width,' ');
            if(is_string($right)){
                $right = [$right];
            }
            foreach($right as $row){
                $out[] = "<green>{$left}</green><white>{$row}</white>";
                $left = str_repeat(' ',strlen($left)); // Only show once
            }
        }

        return $this->indentText(implode("\n",$out),2);
    }

    protected function getMaxWidth(array $array){
        $maxLength = 0;
        foreach($array as $left => $right){
            $width = strlen($left);
            if($width>$maxLength){
                $maxLength = $width;
            }
        }
        return $maxLength;
    }

    /**
     * Only use for descriptions etc due to colors
     *
     * @param string $string
     * @param integer $indent
     * @return void
     */
    protected function wrapText(string $string, int $indent = 0){
        $string = wordwrap($string,self::WIDTH);
        if($indent > 0){
            $string = $this->indentText($string, $indent);
        }
        return $string;
    }

    protected function indentText(string $string,int $indent){
        $padding = str_repeat(' ',$indent);
        return $padding . str_replace("\n","\n{$padding}",$string);
    }


}