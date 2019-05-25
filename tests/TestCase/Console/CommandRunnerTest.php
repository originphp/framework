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

use Origin\Console\CommandRunner;
use Origin\TestSuite\Stub\ConsoleOutput;
use Origin\Console\ConsoleIo;
use Origin\Command\DbCreateCommand;
use App\Command\SaySomethingCommand;
use App\Command\CacheResetCommand;

use Origin\Console\Exception\StopExecutionException;

class MockCommandRunner extends CommandRunner {
    function io(){
        return $this->io;
    }
}
class CommandRunnerTest extends \PHPUnit\Framework\TestCase
{

    public function setUp(){
        $this->out = new ConsoleOutput();
    }

    public function commandRunner(){
        return new CommandRunner(new ConsoleIo( $this->out, $this->out));
    }

    public function testConstructor(){
        $runner = new MockCommandRunner();
        $this->assertInstanceOf(ConsoleIo::class,$runner->io());
    }
    public function testRunNoArgs(){
        $runner = $this->commandRunner();
        $runner->run([]);
        $this->assertContains('console <command> [options] [arguments]',$this->out->read());

$expected = <<< EOF
<code>db:create       </code><text>Creates the database for the datasource</text>
<code>db:drop         </code><text>Drops the database for the datasource</text>
<code>db:migrate      </code><text>Runs and rolls back migrations</text>
<code>db:reset        </code><text>Drops the database and then runs setup</text>
<code>db:schema:dump  </code><text>Dumps the schema to a sql file</text>
<code>db:schema:load  </code><text>Loads the schema from a sql file</text>
<code>db:seed         </code><text>Seeds the database with initial records</text>
<code>db:setup        </code><text>Creates the database,loads schema and seeds the database</text>
EOF;
        $this->assertContains($expected,$this->out->read()); // Framework NS
        $this->assertContains('<code>say-hello       </code><text>A command to say something</text>',$this->out->read()); // App namespace
    }

    public function testFindCommand(){
        $result = $this->commandRunner()->findCommand('say-hello');
        $this->assertInstanceOf(SaySomethingCommand::class,$result);
        $result = $this->commandRunner()->findCommand('db:create'); // standard name 
        $this->assertInstanceOf(DbCreateCommand::class,$result);
        $result = $this->commandRunner()->findCommand('cache:reset'); // standard name + in app folder
        $this->assertInstanceOf(CacheResetCommand::class,$result);
        $this->assertNull($this->commandRunner()->findCommand('purple-disco-machine:player'));
    }

    public function testRun(){
       $result = $this->commandRunner()->run([
           '/vendor/somescript.php',
           'say-hello',
           '--color=blue',
           'jim'
           ]);

        $this->assertTrue($result);
        $this->assertContains('<blue>Hello jim</blue>',$this->out->read());
    }

    public function testRunUnkownCommand(){
        $this->assertFalse($this->commandRunner()->run([
            '/vendor/somescript.php',
            'purple-disco-machine:player',
        ]));
    }
    /**
     * throw a Duplicate database: 7 ERROR:  database "origin" already exists
     *
     * @return void
     */
    public function testRunError(){

        $result = $this->commandRunner()->run([
            '/path-to-script/script.php',
            'db:create',
        ]);
        $this->assertFalse($result);
    }
}
