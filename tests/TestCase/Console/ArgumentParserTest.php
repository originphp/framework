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

    public function testRequiredOptionDefault(){
        $ap = new ArgumentParser();
        $this->expectException(ConsoleException::class);
        $ap->addOption('value1',['type'=>'string','required'=>true,'default'=>'irrelevant']);
    }

    public function testOptionInvalidType(){
        $ap = new ArgumentParser();
        $this->expectException(ConsoleException::class);
        $ap->addOption('value1',['type'=>'<-o->']);
    }

    public function testRequiredArgument(){
        $ap = new ArgumentParser();
        $ap->addArgument('value1',['type'=>'string','required'=>true]);
        $this->expectException(ConsoleException::class);
        $ap->parse([]);
    }

    public function testArgumentRequiredAfterOptional(){
        $ap = new ArgumentParser();
        $ap->addArgument('value1',['type'=>'string']);
        $this->expectException(ConsoleException::class);
        $ap->addArgument('value2',['type'=>'string','required'=>true]);
    }

    public function testArgumentAfterArray(){
        $ap = new ArgumentParser();
        $ap->addArgument('value1',['type'=>'array']);
        $this->expectException(ConsoleException::class);
        $ap->addArgument('value2',['type'=>'string']);
    }

    public function testParseBoolean(){
        $ap = new ArgumentParser();
        $ap->addOption('value1',['type'=>'boolean','short'=>'a']);
        $ap->addOption('value2',['type'=>'boolean','short'=>'b']);
        $ap->addOption('value3',['type'=>'boolean']); // check false
        $ap->addArgument('value4',['type'=>'boolean']);

        list($options,$arguments) = $ap->parse(['--value1','-b','true']);
        $this->assertEquals(true,$options['value1']);
        $this->assertEquals(true,$options['value2']);
        $this->assertEquals(false,$options['value3']);
        $this->assertEquals(true,$arguments['value4']);
    }

    public function testParseInteger(){
        $ap = new ArgumentParser();
        $ap->addOption('value1',['type'=>'integer']);
        $ap->addArgument('value2',['type'=>'integer']);
        list($options,$arguments) = $ap->parse(['--value1=1234','1000']);
        $this->assertEquals(1234,$options['value1']);
        $this->assertEquals(1000,$arguments['value2']);
    }

    public function testParseUnkownLongOption(){
        $ap = new ArgumentParser();
        $this->expectException(ConsoleException::class);
        $ap->parse(['--value1=1234']);
    }

    public function testParseUnkownShortOption(){
        $ap = new ArgumentParser();
        $this->expectException(ConsoleException::class);
        $ap->parse(['-v=1234']);
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
   

    public function testBuildHelp(){
        $ap = new ArgumentParser();
        $ap->setCommand('foo');
        $ap->setDescription(['This is a description']);
        $ap->setEpilog(['This is epilog']);
        $ap->setUsage(['foo dosomething']);
        $ap->setHelp(['This is additional help']);
 
        $help = $ap->help();
       
        $this->assertContains('This is a description',$help);
        $this->assertContains('foo [options] [arguments]',$help);
        $this->assertContains('foo dosomething',$help);
        $this->assertContains('This is epilog',$help);
        $this->assertContains('This is additional help',$help);
    }
    public function testBuildHelpWithArguments(){
        $ap = new ArgumentParser();
        $ap->setCommand('foo');
       
        $ap->addArgument('something',['description'=>['Line #1','Line #2']]);
        $help = $ap->help();
     
        $this->assertContains('Line #1',$help);
        $this->assertContains('Line #2',$help);
        $this->assertContains('foo [options] [something]',$help);
    }

    public function testBuildHelpWithOptions(){
        $ap = new ArgumentParser();
        $ap->setCommand('foo');
       
        $ap->addArgument('something',['description'=>['Line #1','Line #2']]);
        $help = $ap->help();
     
        $this->assertContains('Line #1',$help);
        $this->assertContains('Line #2',$help);
        $this->assertContains('foo [options] [something]',$help);
    }

    public function testBuildHelpWithArgumentsRequired(){
        $ap = new ArgumentParser();
        $ap->setCommand('foo');
        $ap->addArgument('bar',['required'=>true]);
        $help = $ap->help();
        
        $this->assertContains('foo [options] bar',$help);
    }
    public function testBuildHelpWithOptionsRequired(){
        $ap = new ArgumentParser();
        $ap->setCommand('foo');
        $ap->addOption('bar',['required'=>true]);
        $help = $ap->help();
        
        $this->assertContains('foo --bar [options] [arguments]',$help);
    }
    public function testBuildHelpSubCommands(){
        $ap = new ArgumentParser();
        $ap->setCommand('foo');
        
        $ap->addCommand('[bar]',['description'=>'The part after foo']);
        $help = $ap->help();
        
        $this->assertContains('foo command [options] [arguments]',$help);
        $this->assertContains('[bar]',$help);
        $this->assertContains('The part after foo',$help);
    }


}
