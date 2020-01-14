<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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
use Origin\Console\ConsoleInput;
use Origin\Core\Exception\Exception;
use Origin\TestSuite\Stub\ConsoleOutput;

class MockConsoleInput extends ConsoleInput
{
    public function set($result)
    {
        $this->in = $result;
    }
    public function read() : ?string
    {
        return $this->in;
    }
}

class ConsoleIoTest extends \PHPUnit\Framework\TestCase
{
    public function io()
    {
        $this->stdout = new ConsoleOutput();
        $this->stderr = new ConsoleOutput();

        $this->stdout->mode(ConsoleOutput::RAW);
        $this->stderr->mode(ConsoleOutput::RAW);

        $this->stdin = new MockConsoleInput();

        return new ConsoleIo($this->stdout, $this->stderr, $this->stdin);
    }
   
    public function testOut()
    {
        $io = $this->io();
        $io->out('Line #1');
        $this->assertEquals("Line #1\n", $this->stdout->read());
    }

    public function testErr()
    {
        $io = $this->io();
        $io->err('error #1');
        $this->assertEquals("error #1\n", $this->stderr->read());
    }

    public function testWrite()
    {
        $io = $this->io();
        $io->write('Line #1');
        $this->assertEquals('Line #1', $this->stdout->read());
    }

    public function testOverwrite()
    {
        $io = $this->io();

        $io->write('downloading...');
        $this->assertEquals('downloading...', $this->stdout->read());
        $io->overwrite('completed');
  
        $output = $this->stdout->read();
        $this->assertNotEquals('downloading...completed', $output);
      
        $this->assertEquals(43, strlen($this->stdout->read())); // Not sure how to test this
    }

    public function testMultiLine()
    {
        $io = $this->io();
        $this->stdout->mode(ConsoleOutput::COLOR);
        
        $io->write(['first line','second line']);
        $this->assertEquals("first line\nsecond line", $this->stdout->read());
        $io->overwrite('third line');
        $this->assertEquals(67, strlen($this->stdout->read())); // Not sure how to test this
    }

    public function testTitle()
    {
        $io = $this->io();
        $io->title('My Title');
        $expected = [
            '<heading>My Title</heading>',
            '<heading>========</heading>',
            "\n",
        ];

        $this->assertEquals(implode("\n", $expected), $this->stdout->read());
    }

    public function testHeading()
    {
        $io = $this->io();
        $io->heading('My Heading');
        $expected = [
            '<heading>My Heading</heading>',
            '<heading>----------</heading>',
            "\n",
        ];

        $this->assertEquals(implode("\n", $expected), $this->stdout->read());
    }

    public function testText()
    {
        $io = $this->io();
        $io->text(['the quick brown fox','lorepsum something']);
        $expected = [
            '  the quick brown fox',
            '  lorepsum something'. "\n",
           
        ];
        $this->assertEquals(implode("\n", $expected), $this->stdout->read());
    }

    public function testTable()
    {
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

        $this->assertEquals($expected, $this->stdout->read());

        $io = $this->io();
        $io->table([
            ['text a','text b','text c'],
            ['text d','text e','text f'],
            ['text g','text h','text i'],
        ], false);

        $expected = <<<EOF
+---------+---------+---------+
| text a  | text b  | text c  |
| text d  | text e  | text f  |
| text g  | text h  | text i  |
+---------+---------+---------+

EOF;
        $this->assertEquals($expected, $this->stdout->read());
    }

    public function testList()
    {
        $io = $this->io();
        $io->list(['buy milk']);
        $io->list('buy bread', '-');
        $expected = <<<EOF
  * buy milk
  - buy bread

EOF;
        $this->assertEquals($expected, $this->stdout->read());
    }
    
    public function testFormat()
    {
        $io = $this->io();
        
        $result = $io->format('Hello', ['color' => 'red','background' => 'white']);
        $this->assertEquals("\033[31;107mHello\033[39;49m", $result);
    }

    public function testInfo()
    {
        $io = $this->io();
        $io->info('some message');
        $this->assertEquals("\033[97;44;1msome message\033[39;49;22m\n", $this->stdout->read());
    }
    public function testSuccess()
    {
        $io = $this->io();
        $io->success('some message');
        $this->assertEquals("\033[97;42;1msome message\033[39;49;22m\n", $this->stdout->read());
    }

    public function testWarning()
    {
        $io = $this->io();
        $io->warning('some message');
        $this->assertEquals("\033[30;43;1msome message\033[39;49;22m\n", $this->stderr->read());
    }

    public function testError()
    {
        $io = $this->io();
        $io->error('some message');
        $this->assertEquals("\033[97;101;1msome message\033[39;49;22m\n", $this->stderr->read());
    }

    public function testAlert()
    {
        $io = $this->io();
        $io->alert('An Alert Box', ['background' => 'white','color' => 'black']);
        $this->assertEquals('32584b2056b87965eb1224d0d3d95ede', md5($this->stdout->read()));
    }
    public function testBlock()
    {
        $io = $this->io();
        $io->block('A Block', ['background' => 'yellow','color' => 'black']);
        $this->assertEquals('09ec36ad34e84fa021cc8cbe4fd3d9db', md5($this->stdout->read()));
    }

    public function testStdin()
    {
        $io = new ConsoleIo();
        $this->assertInstanceOf(ConsoleInput::class, $io->stdin());
    }

    public function testStdout()
    {
        $io = $this->io();
    
        $this->assertInstanceOf(ConsoleOutput::class, $io->stdout());
    }
    public function testStderr()
    {
        $io = $this->io();
        $this->assertInstanceOf(ConsoleOutput::class, $io->stderr());
    }

    public function testClear()
    {
        $io = $this->io();
        $io->out('Hello World!');
        $io->clear();
        $this->assertStringContainsString("\033c", $this->stdout->read());
    }

    public function testStyles()
    {
        $io = $this->io();
        $this->assertNotEmpty($io->styles());
        $io->styles('fire', ['color' => 'red']);
        $this->assertEquals(['color' => 'red'], $io->styles('fire'));
    }

    public function testProgressBar()
    {
        $io = $this->io();
        $io->progressBar(10, 20, ['ansi' => false]);
        $this->assertStringContainsString('[#########################                         ] 50%', $this->stdout->read());
    }

    public function testAsk()
    {
        $io = $this->io();
        $this->stdin->set('y');
        $this->assertEquals('y', $io->ask('continue?'));
        $this->assertStringContainsString('continue?', $this->stdout->read());

        $io = $this->io();
        $this->stdin->set('');
        $this->assertEquals('n', $io->ask('continue?', 'n'));
        $this->assertStringContainsString('continue?', $this->stdout->read());
    }

    public function testAskChoice()
    {
        $io = $this->io();
        $this->stdin->set('y');
        $this->assertEquals('y', $io->askChoice('continue?', ['y','n']));
        $this->assertStringContainsString('continue?', $this->stdout->read());

        $io = $this->io();
        $this->stdin->set('');
        $this->assertEquals('n', $io->askChoice('continue?', ['y','n'], 'n'));
        $this->assertStringContainsString('continue?', $this->stdout->read());
    }

    public function testStatus()
    {
        $io = $this->io();
        $io->status('ok', 'All Good');
        $this->assertStringContainsString('<white>[</white> <green>OK</green> <white>] All Good</white>', $this->stdout->read());

        $io = $this->io();
        $io->status('error', 'An Error Occured');
        $this->assertStringContainsString('<white>[</white> <red>ERROR</red> <white>] An Error Occured</white>', $this->stdout->read());

        $io = $this->io();
        $io->status('ignore', 'The Status Text');
        $this->assertStringContainsString('<white>[</white> <yellow>IGNORE</yellow> <white>] The Status Text</white>', $this->stdout->read());

        $io = $this->io();
        $io->status('skipped', 'The Status Text');
        $this->assertStringContainsString('<white>[</white> <cyan>SKIPPED</cyan> <white>] The Status Text</white>', $this->stdout->read());

        $io = $this->io();
        $io->status('started', 'The Status Text');
        $this->assertStringContainsString('<white>[</white> <green>STARTED</green> <white>] The Status Text</white>', $this->stdout->read());

        $io = $this->io();
        $io->status('stopped', 'The Status Text');
        $this->assertStringContainsString('<white>[</white> <yellow>STOPPED</yellow> <white>] The Status Text</white>', $this->stdout->read());

        $this->expectException(Exception::class);
        $io = $this->io();
        $io->status('raining', 'The Status Text');
    }
}
