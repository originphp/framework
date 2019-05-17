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
use Origin\Console\ConsoleOutput;
use Origin\Console\ConsoleInput;

class ConsoleIo
{
    /**
     * Output Stream
     *
     * @var \Origin\Console\ConsoleOutput
     */
    protected $stdout = null;

        /**
     * Error Stream
     *
     * @var \Origin\Console\ConsoleOutput
     */
    protected $stderr = null;

     /**
     * Input Stream
     *
     * @var \Origin\Console\ConsoleInput
     */
    protected $stdin = null;

    /**
     * For the status function
     *
     * @var array
     */
    protected $statusCodes = [
        'ok' => 'green',
        'error' => 'red',
        'ignore' => 'yellow',
        'skipped' => 'cyan',
        'started' => 'green',
        'stopped' => 'yellow',
    ];


    public function __construct(ConsoleOutput $out = null,ConsoleOutput $err = null,ConsoleInput $in = null){
       
        if($out === null){
            $out = new ConsoleOutput('php://stdout');
        }

        if($err === null){
           $err = new ConsoleOutput('php://stderr');
        }
        if($in === null){
           $in = new ConsoleInput('php://stdin');
        }
        $this->stdout = $out;
        $this->stderr = $err;
        $this->stdin = $in;
    }

    /**
     * Outputs line or lines to the stdout adding \n to each line
     *
     * @param  string|array $message
     * @return void
     */
    public function out($message){
        foreach((array) $message as $line){
            $this->stdout->write($line ."\n");
        }
    }

    /**
     * Writes to the output without adding new lines
     *
     * @param string|array $message
     * @return void
     */
    public function write($message){
        foreach((array) $message as $line){
            $this->stdout->write($line);
        }
    }

    /**
     * Outputs line or lines to the stderr
     *
     * @param  string|array $message
     * @return void
     */
    public function err($message){
        foreach((array) $message as $line){
            $this->stderr->write($line ."\n");
        }
    }

     /**
     * A Title style
     *
     * @param string $heading
     * @return void
     */
    public function title(string $title){
   
        $this->out($title);
        $this->out(str_repeat('=',strlen($title)));
        $this->nl();
    }

    /**
     * A heading style
     *
     * @param string $heading1
     * @return void
     */
    public function heading(string $heading){
        $this->out($heading);
        $this->out(str_repeat('-',strlen($heading)));
        $this->nl();
    }

    /**
     * This ouput texts for use with heading,title etc. Text will automatically be indented.
     *
     * @param string|array $text
     * @return void
     */
    public function text($text,array $options =[]){
        $options += ['bullet'=>'*','indent'=>2];
        $text = (array) $text;
        foreach($text as $line){
           $this->out(str_repeat(' ',$options['indent']) . $line);
        }
    }
    /**
     * Draws table
     *
     * @param array $array
     * @param boolean $headers wether first row is headers
     * @return void
     */
    public function table(array $array,$headers=false){
        # Calculate width of each column
        $widths = [];
        foreach($array as $rowIndex => $row){  
            $maxColumnWidth =  0;
            foreach($row as $columnIndex => $cell){
                if(!isset($widths[$columnIndex])){
                    $widths[$columnIndex] = 0;
                }
                $width = strlen($cell) + 4;
                if($width > $widths[$columnIndex]){
                    $widths[$columnIndex] = $width;
                }
            }
        }
       
        $out = [];
        $seperator = '';
        foreach($array[0] as $i => $cell){
            $seperator .= str_pad('+',$widths[$i],'-',STR_PAD_RIGHT);
        }
        $seperator .= '+';
        $out[] = $seperator;

        if($headers){  
            $headers = '|';
            foreach($array[0] as $i => $cell){
                $headers .=  ' ' . str_pad($cell,$widths[$i] - 2,' ',STR_PAD_RIGHT) .'|';
            }
            $out[] = $headers;
            $out[] = $seperator;
            
            array_shift($array);
        }
      
        foreach($array as $row){
            $cells = '|';
            foreach($row as $i => $cell){
                $cells .= ' ' . str_pad($cell,$widths[$i] -2 ,' ',STR_PAD_RIGHT).'|';
            }
            $out[] = $cells;
        }
        $out[] = $seperator;
        $this->out($out);
    }
    
    /**
     * Generates a list of list item
     *
     * @param string|array $elements 'buy milk' or ['buy milk','read the paper']
     * @param array $options Defaults are
     *   - bullet: default *
     *   - indent: level of identation default 2
     *   + (color,background,etc)
     * @return void
     */
    public function list($elements,array $options=[]){
        $options += ['bullet'=>'*','indent'=>2,'color'=>'white'];
   
        foreach((array) $elements as $element){
           $string = str_repeat(' ',$options['indent']) . $options['bullet'] . ' '.  $element;
           $this->formatString($string,$options);
        }
    }

    /**
     * Set a style
     *  
     * @param string $name  e.g fire
     * @param array $options  ['color'=>'white','background'=>'red','blink'=>true]
     * @return void
     */
    public function style(string $name,array $options=[]){
        $this->stdout->style($name,$options);
    }

    /**
     * Formats a line by using array of options. such as color,background
     *
     * @param string $text
     * @param array $options
     * @return void
     */
    private function formatString(string $text,array $options=[]){
     
        $line = $this->stdout->color($text,$options) . "\n";
        $this->stdout->write($line);
    }


    /**
     * Displays a warning block or alert
     *
     * @param string|array $messages line or array of lines
     * @param array $options (background,color,blink,bold,underline)
     * @return void
     */
    public function warning($messages,array $options = []){
        $options += ['background'=>'yellow','color'=>'black','large'=>false];
        if($options['large']){
           $this->alert($messages,$options);
        }
        else{
            $this->block($messages,$options);
        }
    }

    /**
     * Displays a success block or alert
     *
     * @param string|array $messages line or array of lines
     * @param array $options (background,color,blink,bold,underline)
     * @return void
     */
    public function success($messages,array $options = []){
        $options += ['background'=>'cyan','color'=>'white','large'=>false];
        if($options['large']){
           $this->alert($messages,$options);
        }
        else{
            $this->block($messages,$options);
        }
    }

    /**
     * Displays an error block or alert
     *
     * @param string|array $messages line or array of lines
     * @param array $options (background,color,blink,bold,underline)
     * @return void
     */
    public function error($messages,array $options = []){
        $options += ['background'=>'lightRed','color'=>'white','large'=>false];
        if($options['large']){
           $this->alert($messages,$options);
        }
        else{
            $this->block($messages,$options);
        }
    }

    /**
     * Draws a progress bar
     *
     * @param integer $value
     * @param integer $max
     * @return void
     */
    public function progressBar(int $value,int $max) {
        $percent = floor(($value / $max) * 100);
        $left = 100 - $percent;
        $pb = $pb2 = '';
        if($percent){
            $pb = str_repeat("\033[102m \033[0m",floor($percent/2));
        }
        if($left){
            $pb2 = str_repeat( "\033[30;40m \033[0m",floor($left/2));
        }
       
       $this->out("\033[0G\033[2K" .$pb . $pb2 ."\033[92m{$percent}%",false);
       if($percent == 100){
           sleep(1);
          $this->out("\033[0G\033[2K\033[0m",false);
       }
    }

    /**
     * Generates a colourful alert 
     *
     * @param string|array $messages
     * @param array $options
     * @return void
     */
    public function alert($messages,array $options=[]){
        $options += ['background'=>'black','color'=>'white'];

        $this->nl();

        if(!is_array($messages)){
            $messages  = [$messages];
        }
       
        $this->formatString(str_repeat(' ',80 ),$options);
        foreach($messages as $message){
            $this->formatString('  ' . str_pad($message ,80 - 2, " ", STR_PAD_RIGHT),$options);
        }
        $this->formatString(str_repeat(' ',80 ),$options);
        $this->nl();
    }

  /**
     * Warps text in a colourful block
     *
     * @param string|array $messages
     * @param array $options
     * @return void
     */
    public function block($messages,array $options=[]){
        $options += ['background'=>'black','color'=>'white','center'=>false,'padding'=>4];

        $center = STR_PAD_RIGHT;
        if($options['center']){
            $center = STR_PAD_BOTH;
        }
        $this->nl();

        if(!is_array($messages)){
            $messages  = [$messages];
        }
        $maxLength = $this->getMaxLength($messages) + ($options['padding'] * 2);
       
        $this->formatString(str_repeat(' ',$maxLength ),$options);
        foreach($messages as $message){
            $padding = str_repeat(' ',$options['padding']);
            $message = $padding . $message . $padding;
            $this->formatString(str_pad($message ,$maxLength, " ", $center ),$options);
        }
        $this->formatString(str_repeat(' ',$maxLength ),$options);
        $this->nl();
    }


  /**
   * Outputs new lines
   *
   * @param integer $count number of newlines
   * @return void
   */
    public function nl($count=1){
        $this->stdout->write(str_repeat("\n",$count));
    }

    /**
     * Clears the screen
     *
     * @return void
     */
    public function clear(){
        $this->out("\033c");
    }

    /**
     * Asks the user a question and returns the value (or default if set)
     *
     * @param string $prompt The question to ask
     * @param string $default default value if user presses enter
     * @return string
     */
    public function ask(string $prompt, string $default = null)
    {
        $input =  '';
        if ($default) {
            $prompt .= " [{$default}]";
        }

        while ($input === '') {
            $this->out($prompt);
            $this->stdout->write("> ");
            $input = $this->stdin->read();
            if ($input === '' and $default) {
                return $default;
            }
        }
        return $input;
    }

     /**
     * Asks the user a question and returns the value (or default if set)
     *
     * @param string $prompt The question to ask
     * @param array $options ['yes','no']
     * @param string $default default value if user presses enter
     * @return void
     */
    public function askChoice(string $prompt, array $options, string $default = null)
    {
        $input =  $defaultString = '';
        $optionsString = implode('/', $options);
        if ($default) {
            $defaultString = "[{$default}]";
        }
        $extra = " ({$optionsString}) {$defaultString}";

        // Check both uppercase and lower case input
        $options = array_merge(
            array_map('strtolower', $options),
            array_map('strtoupper', $options)
        );

        while ($input === '' OR !in_array($input, $options)) {
            
            $this->out("{$prompt} {$extra}");
            $this->stdout->write("> ");
            $input = $this->stdin->read();
            if ($input === '' and $default) {
                return $default;
            }
        }
        return $input;
    }

    /**
     * Creates a file
     *
     * @param string $filename
     * @param string $contents
     * @param boolean $forceOverwrite
     * @return void
     */
    public function createFile(string $filename,string $contents, $forceOverwrite=false){
        if(file_exists($filename) AND $forceOverwrite === false){
            $this->warning("File {$filename} already exists");
            $input = $this->askChoice('Do you want to overwrite?',['y','n'],'n');
            if($input === 'n'){
                return false;
            }
        }

        try 
        {
            $directory = dirname($filename);
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }
            return file_put_contents($filename,$contents);
        }
        catch(Exception $e) {
            return false;
        }
        
    }


    /**
     * Displays a status
     *
     * @param string $type e.g. ok, error, ignore
     * @param string $message
     * @return void
     */
    public function status(string $status, string $message)
    {
        if (isset($this->statusCodes[status])) {
            $color = $this->statusCodes[$status];
            $status = strtoupper($status);
            $this->out("<text>[</text> <{$color}>{$status}</{$color}> <text>] {$message}</text>");
            return;
        }
        throw new ConsoleException(sprintf('Unkown status %s', $status));
    }

    

    /**
     * Get the max length of a an array of lines
     *
     * @param array $lines
     * @return int
     */
    protected function getMaxLength(array $lines){
        $maxLength = 0;
        foreach($lines as $line){
            $length = strlen($line);
            if($length > $maxLength){
                $maxLength = $length;
            }
        }
        return $maxLength;

    }
}