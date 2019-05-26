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

namespace Origin\Test\Console\ConsoleIo;

use Origin\Console\ConsoleIo;
use Origin\TestSuite\Stub\ConsoleOutput;
use Origin\Console\ConsoleInput;

class ConsoleIoTest extends \PHPUnit\Framework\TestCase
{

    public function io(){
        $this->stdout = new ConsoleOutput();
        $this->stderr = new ConsoleOutput();

        $this->stdout->mode(ConsoleOutput::RAW);
        $this->stderr->mode(ConsoleOutput::RAW);

        return new ConsoleIo($this->stdout, $this->stderr);
    }
   
    public function testOut(){
        $io = $this->io();
        $io->out('Line #1');
        $this->assertEquals("Line #1\n",$this->stdout->read());
    }

    public function testErr(){
        $io = $this->io();
        $io->err('error #1');
        $this->assertEquals("error #1\n",$this->stderr->read());
    }

    public function testWrite(){
        $io = $this->io();
        $io->write('Line #1');
        $this->assertEquals("Line #1",$this->stdout->read());
    }


    public function testOverwrite(){
        $io = $this->io();
        $io->write('downloading...');
        $this->assertEquals("downloading...",$this->stdout->read());
        $io->overwrite('completed',false);
        $this->assertEquals(35,strlen($this->stdout->read())); // Not sure how to test this
    }

    public function testTitle(){
        $io = $this->io();
        $io->title('My Title');
        $expected = [
            '<heading>My Title</heading>',
            '<heading>========</heading>',
            "\n"
        ];

        $this->assertEquals(implode("\n",$expected),$this->stdout->read());
    }

    public function testHeading(){
        $io = $this->io();
        $io->heading('My Heading');
        $expected = [
            '<heading>My Heading</heading>',
            '<heading>----------</heading>',
            "\n"
        ];

        $this->assertEquals(implode("\n",$expected),$this->stdout->read());
    }

    public function testText(){
        $io = $this->io();
        $io->text(['the quick brown fox','lorepsum something']);
        $expected = [
            '  the quick brown fox',
            '  lorepsum something'. "\n",
           
        ];
        $this->assertEquals(implode("\n",$expected),$this->stdout->read());
    }

    public function testTable(){
        $io = $this->io();
        $io->table([
            ['heading 1','heading 2','heading 3'],
            ['text a','text b','text c'],
            ['text d','text e','text f'],
            ['text g','text h','text i'],
        ]);

$expected = <<<EOF
+------------+------------+------------+
| heading 1  | heading 2  | heading 3  |
+------------+------------+------------+
| text a     | text b     | text c     |
| text d     | text e     | text f     |
| text g     | text h     | text i     |
+------------+------------+------------+

EOF;

        $this->assertEquals($expected,$this->stdout->read());

        $io = $this->io();
        $io->table([
            ['text a','text b','text c'],
            ['text d','text e','text f'],
            ['text g','text h','text i'],
        ],false);

$expected = <<<EOF
+---------+---------+---------+
| text a  | text b  | text c  |
| text d  | text e  | text f  |
| text g  | text h  | text i  |
+---------+---------+---------+

EOF;
$this->assertEquals($expected,$this->stdout->read());
    }

    public function testList(){
        $io = $this->io();
        $io->list(['buy milk']);
        $io->list('buy bread','-');
$expected = <<<EOF
  * buy milk
  - buy bread

EOF;
$this->assertEquals($expected,$this->stdout->read());
    }
    
    public function testFormat(){
        $io = $this->io();
        
        $result = $io->format('Hello',['color'=>'red','background'=>'white']);
        $this->assertEquals("\033[31;107mHello\033[39;49m",$result);
    }

    public function testInfo(){
        $io = $this->io();
        $io->info('some message');
        $this->assertEquals("\033[97;44;1msome message\033[39;49;22m\n",$this->stdout->read());
    }
    public function testSuccess(){
        $io = $this->io();
        $io->success('some message');
        $this->assertEquals("\033[97;42;1msome message\033[39;49;22m\n",$this->stdout->read());
    }

    public function testWarning(){
        $io = $this->io();
        $io->warning('some message');
        $this->assertEquals("\033[30;43;1msome message\033[39;49;22m\n",$this->stderr->read());
    }

    public function testError(){
        $io = $this->io();
        $io->error('some message');
        $this->assertEquals("\033[97;101;1msome message\033[39;49;22m\n",$this->stderr->read());
    }

    public function testAlert(){
        $io = $this->io();
        $io->alert('An Alert Box',['background'=>'white','color'=>'black']);
        $this->assertEquals('32584b2056b87965eb1224d0d3d95ede',md5($this->stdout->read()));
    }
    public function testBlock(){
        $io = $this->io();
        $io->block('A Block',['background'=>'yellow','color'=>'black']);
        $this->assertEquals('09ec36ad34e84fa021cc8cbe4fd3d9db',md5($this->stdout->read()));
    }

    public function testStdin(){
        $io = new ConsoleIo();
        $this->assertInstanceOf(ConsoleInput::class,$io->stdin());
    }

    public function testStdout(){
        $io = $this->io();
    
        $this->assertInstanceOf(ConsoleOutput::class,$io->stdout());
    }
    public function testStderr(){
        $io = $this->io();
        $this->assertInstanceOf(ConsoleOutput::class,$io->stderr());
    }

    public function testClear(){
        $io = $this->io();
        $io->out('Hello World!');
        $io->clear();
        $this->assertContains("\033c",$this->stdout->read());
    }

    public function testStyles(){
        $io = $this->io();
        $this->assertNotEmpty($io->styles());
        $io->styles('fire',['color'=>'red']);
        $this->assertEquals(['color'=>'red'],$io->styles('fire'));
    }
}
