<?php
namespace App\Test\Publisher;

use Origin\Process\Process;
use Origin\TestSuite\OriginTestCase;

class ProcessTest extends OriginTestCase
{
    public function testExecute()
    {
        $process = new Process(['git','--version']);
        $process->execute();
        $this->assertStringContains('git version', $process->getOutput());

        $this->assertEmpty($process->getErrorOutput());
        $this->assertEquals(0, $process->exitCode());
    }

    public function testExecuteENV()
    {
        $process = new Process('echo "the ${FOO} brown fox"', ['escape' => false,'env' => ['FOO' => 'quick']]);
        $process->execute();
        $this->assertStringContains('the quick brown fox', $process->getOutput());

        $this->assertEmpty($process->getErrorOutput());
        $this->assertEquals(0, $process->exitCode());
    }

    public function testExecuteError()
    {
        $process = new Process(['cat','foo']);
        $process->execute();
        $this->assertStringContains('cat: foo: No such file or directory', $process->getErrorOutput());
       
        $this->assertEmpty($process->getOutput());
        $this->assertNotEmpty($process->getErrorOutput());
        $this->assertEquals(1, $process->exitCode());
    }
}
