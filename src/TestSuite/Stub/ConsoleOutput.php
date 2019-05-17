<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\TestSuite\Stub;

use Origin\Console\ConsoleOutput as BaseConsoleOutput;
class ConsoleOutput extends BaseConsoleOutput
{
    protected $out = [];

    public function write($data,$newLine = true)
    {
         if(!is_array($data)){
            $data = [$data];
        }
        foreach($data as $line){
            $this->out[] = $line;
        }
    }

    public function read(){
        return implode("\n",$this->out);
    }
    public function messages(){
        return $this->out;
    }
}
