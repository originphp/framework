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
declare(strict_types = 1);
namespace Origin\TestSuite;

use Origin\Console\ConsoleIo;
use Origin\Console\ConsoleInput;
use Origin\Console\CommandRunner;
use Origin\Console\Command\Command;
use Origin\TestSuite\Stub\ConsoleOutput;

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
     * This is the command object.
     *
     * @var \Origin\Console\Command\Command
     */
    protected $command = null;

    /**
     * Holds the result from the exec.
     *
     * @internal this has been renamed from result to strange bug in PHP 7.3 when reflected
     * which was causing segmentation faults phpunit/phpstan
     *
     * @var int|null
     */
    protected $commandResult = null;

    /**
     * Gets the stdout output (standard non errors)
     *
     * @return string
     */
    public function output(): string
    {
        return $this->stdout->read();
    }

    /**
    * Gets the stderr output (Errors)
    *
    * @return string
    */
    public function error(): string
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
    public function exec(string $command, array $input = []): void
    {
        $this->commandResult = null;

        $this->stdout = new ConsoleOutput();
        $this->stderr = new ConsoleOutput();
        $this->stdin = $this->getMockBuilder(ConsoleInput::class)
            ->disableOriginalConstructor()
            ->setMethods(['read'])
            ->getMock();

        $x = 0;
        foreach ($input as $data) {
            $this->stdin->expects(
                $this->at($x)
            )
                ->method('read')
                ->will(
                    $this->returnValue($data)
                );
            ++$x;
        }

        $argv = $this->splitCommand("console {$command}");
        list($namespace, $class) = namespacesplit(get_class($this));

        $io = new ConsoleIo($this->stdout, $this->stderr, $this->stdin);
        $commandRunner = new CommandRunner($io);
        $this->commandResult = $commandRunner->run($argv);
        $this->command = $commandRunner->command();
    }

    /**
     * Splits a command line argument, and looks for data enclosed in double quotes ONLY
     *
     * @param string $command
     * @return array
     */
    protected function splitCommand(string $command) : array
    {
        $args = [];
        $arg = '';
        $len = strlen($command);
        $enclosed = false;
        for ($i=0;$i<$len;$i++) {
            $char = substr($command, $i, 1);
            if ($char === ' ' && $enclosed === false) {
                if ($arg) {
                    $args[] = $arg;
                }
                $arg = '';
                continue;
            }

            if ($enclosed === false && $char === '"') {
                $arg = $arg . '"';
                $enclosed = true;
                continue;
            }

            if ($enclosed === true && $char === '"') {
                $arg =  $arg . '"';
                $enclosed = false;
                continue;
            }

            $arg .= $char;
        }
        $args[] = $arg;

        return $args;
        /*
        $placeHolders = [];
        preg_match_all('/"([^"]*)"/', $command, $matches);
        foreach ($matches[0] as $needle) {
            $placeHolder = '{P' . count($placeHolders) . '}';
            $command = str_replace($needle, $placeHolder, $command);
            $placeHolders[$placeHolder] = $needle;
        }
        $args = str_getcsv($command, ' ');
        foreach ($args as &$arg) {
            foreach ($placeHolders as $find => $replace) {
                $arg = str_replace($find, $replace, $arg);
            }
        }
        return $args;*/
    }

    /**
     * Asserts that console output contains text.
     *
     * @param string $needle The text that you want to assert that is in the output
     */
    public function assertOutputContains(string $needle): void
    {
        $this->assertStringContainsString($needle, $this->stdout->read());
    }

    /**
     * Asserts that console output does not contains text.
     *
     * @param string $needle The text that you want to assert that is in the output
     */
    public function assertOutputNotContains(string $needle): void
    {
        $this->assertStringNotContainsString($needle, $this->stdout->read());
    }

    /**
     * Assert output against a regex expression
     *
     * @param string $message
     */
    public function assertOutputRegExp(string $expression): void
    {
        $this->assertMatchesRegularExpression($expression, $this->stdout->read());
    }

    /**
     * Asserts that console output is empty.
     */
    public function assertOutputEmpty(): void
    {
        $this->assertStringContainsString('', $this->stdout->read());
    }

    /**
     * Asserts that the command was run and was not halted using command::abort().
     */
    public function assertExitSuccess(): void
    {
        $this->assertEquals(Command::SUCCESS, $this->commandResult);
    }

    /**
     * Asserts that the command was run was halted using command::abort().
     */
    public function assertExitError(): void
    {
        $this->assertEquals(Command::ERROR, $this->commandResult);
    }

    /**
     * Asserts a particular exit code
     */
    public function assertExitCode(int $exitCode): void
    {
        $this->assertEquals($exitCode, $this->commandResult);
    }

    /**
     * Assert an error contains.
     *
     * @param string $message
     */
    public function assertErrorContains(string $message): void
    {
        $this->assertStringContainsString($message, $this->stderr->read());
    }

    /**
     * Assert an error does not contain a string
     *
     * @param string $message
     */
    public function assertErrorNotContains(string $message): void
    {
        $this->assertStringNotContainsString($message, $this->stderr->read());
    }

    /**
     * Assert error output against a regex expression
     *
     * @param string $message
     * @return void;
     */
    public function assertErrorRegExp(string $expression): void
    {
        $this->assertMatchesRegularExpression($expression, $this->stderr->read());
    }

    /**
     * Returns the Command Object.
     *
     * @return \Origin\Console\Command\Command
     */
    public function command(): Command
    {
        return $this->command;
    }
}
