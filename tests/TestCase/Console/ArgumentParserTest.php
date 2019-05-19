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

namespace Origin\Test\Console;

use Origin\Console\ArgumentParser;
use Origin\Console\Exception\ConsoleException;

class ArgumentParserTest extends \PHPUnit\Framework\TestCase
{

    public function testParseString(){
        $ap = new ArgumentParser();
        $ap->addOption('text_a',['type'=>'string','short'=>'a']);
        $ap->addOption('text_b',['type'=>'string','short'=>'b']);
        $ap->addArgument('text_c',['type'=>'string']);

        list($options,$arguments) = $ap->parse(['--text_a=foo','-b=bar','foobar']);
        $this->assertEquals('foo',$options['text_a']);
        $this->assertEquals('bar',$options['text_b']);
        $this->assertEquals('foobar',$arguments['text_c']);
       
    }

    public function testDefault(){
        $ap = new ArgumentParser();
        $ap->addOption('value1',['type'=>'string','default'=>'foo']);
    
        list($options,$arguments) = $ap->parse([]);
       $this->assertEquals('foo',$options['value1']);
    }

    public function testRequiredOption(){
        $ap = new ArgumentParser();
        $ap->addOption('value1',['type'=>'string','required'=>true]);
        $this->expectException(ConsoleException::class);
        $ap->parse([]);
    }

    public function testRequiredArgument(){
        $ap = new ArgumentParser();
        $ap->addArgument('value1',['type'=>'string','required'=>true]);
        $this->expectException(ConsoleException::class);
        $ap->parse([]);
    }

    public function testParseBoolean(){
        $ap = new ArgumentParser();
        $ap->addOption('value1',['type'=>'boolean','short'=>'a']);
        $ap->addOption('value2',['type'=>'boolean','short'=>'b']);
        $ap->addArgument('value3',['type'=>'boolean']);

        list($options,$arguments) = $ap->parse(['--value1','-b','true']);
        $this->assertEquals(true,$options['value1']);
        $this->assertEquals(true,$options['value2']);
        $this->assertEquals(true,$arguments['value3']);
    }

    public function testArray(){
        $ap = new ArgumentParser();

        $ap->addArgument('controller',['type'=>'string']);
        $ap->addArgument('actions',['type'=>'array']);
        list($options,$arguments) = $ap->parse(['Products','index','add','edit']);
        $this->assertEquals('Products',$arguments['controller']);
        $this->assertEquals(['index','add','edit'],$arguments['actions']);
    }

    public function testHash(){
        $ap = new ArgumentParser();
        $ap->addArgument('model',['type'=>'string']);
        $ap->addArgument('schema',['type'=>'hash']);
        list($options,$arguments) = $ap->parse(['Product','name:string','description:text','error']);
        $this->assertEquals('Product',$arguments['model']);
    
        $this->assertEquals(['name'=>'string','description'=>'text','error'],$arguments['schema']);
    }
   

}
