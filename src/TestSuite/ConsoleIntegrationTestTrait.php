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
 *
 * @see       https://www.originphp.com
 *
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\TestSuite;

use Origin\Console\ConsoleInput;
use Origin\TestSuite\Stub\ConsoleOutput;
use Origin\Console\ShellDispatcher;
use Origin\Console\CommandRunner;
use Origin\Console\ConsoleIo;

/**
 * A way to test controllers from a higher level.
 */
trait ConsoleIntegrationTestTrait
{
    /**
     * Holds the standard console output.
     *
     * @var \Origin\Console\ConsoleOutput
     */
    protected $stdout = null;

    /**
     * Holds the console output for errors.
     *
     * @var \Origin\Console\ConsoleOutput
     */
    protected $stderr = null;

    /**
     * Holds the console output.
     *
     * @var \Origin\Console\ConsoleInput
     */
    protected $stdin = null;

    /**
     * This holds the legacy shell object.
     *
     * @var \Origin\Console\Shell;
     */
    protected $shell = null;

    /**
     * This is the command object.
     *
     * @var \Origin\Console\Command
     */
    protected $command = null;

    /**
     * Holds the result from the exec.
     *
     * @var bool
     */
    protected $result = null;

    /**
     * Gets the stdout output (standard non errors)
     *
     * @return string
     */
    public function output()
    {
        return $this->stdout->read();
    }

    /**
     * Gets the stderr output (Errors)
     *
     * @return string
     */
    public function errorOutput()
    {
        return $this->stderr->read();
    }

    /**
     * Executes a console command.
     *
     * @param string $command e.g. db:schema:load
     * @param array  $input   array of input that will be used as response to prompts
     *
     * @return string $output The messages from the console output
     */
    public function exec(string $command, array $input = [])
    {
        $this->shell = $this->result = null;

        $this->stdout = new ConsoleOutput();
        $this->stderr = new ConsoleOutput();
        $this->stdin = $this->getMockBuilder(ConsoleInput::class)
                        ->disableOriginalConstructor()
                        ->setMethods(['read'])
                        ->getMock();

        $x = 0;
        foreach ($input as $data) {
            $this->stdin->expects(
                $this->at($x))
                ->method('read')
                ->will($this->returnValue($data)
            );
            ++$x;
        }

        $argv = explode(' ', "console {$command}");
        list($namespace, $class) = namespacesplit(get_class($this));

        // Handle Legacy
        if (substr($class, -9) === 'ShellTest') {
            $this->stderr = $this->stdout; // Fixture Issue
            $dispatcher = new ShellDispatcher($argv, $this->stdout, $this->stdin);
            $this->result = $dispatcher->start();
            $this->shell = $dispatcher->shell();
        } else {
            $io = new ConsoleIo($this->stdout, $this->stderr, $this->stdin);
            $commandRunner = new CommandRunner($io);
            $this->result = $commandRunner->run($argv);
            $this->command = $commandRunner->command();
        }
    }

    /**
     * Asserts that console output contains text.
     *
     * @param string $needle The text that you want to assert that is in the output
     */
    public function assertOutputContains(string $needle)
    {
        $this->assertContains($needle, $this->stdout->read());
    }

    /**
     * Asserts that console output is empty.
     */
    public function assertOutputEmpty()
    {
        $this->assertContains('', $this->stdout->read());
    }

    /**
     * Asserts that the command was run and was not halted using command::abort().
     */
    public function assertExitSuccess()
    {
        if ($this->result === false) {
            printf($this->stderr->read());
        }
        $this->assertTrue($this->result);
    }

    /**
     * Asserts that the command was run was halted using command::abort().
     */
    public function assertExitError()
    {
        $this->assertFalse($this->result);
    }

    /**
     * Assert an error contains.
     *
     * @param string $message
     */
    public function assertErrorContains(string $message)
    {
        $this->assertContains($message, $this->stderr->read());
    }

    /**
     * Returns the Command Object.
     *
     * @return \Origin\Command\Command
     */
    public function command()
    {
        return $this->command;
    }
}