<?php
namespace App\Test\Publisher;

use LogicException;
use RuntimeException;
use Origin\TestSuite\OriginTestCase;
use Origin\Process\BackgroundProcess;

class BackgroundProcessTest extends OriginTestCase
{
    public function testPid()
    {
        $process = new BackgroundProcess('sleep 1', ['escape' => false]);
        $this->assertNull($process->getPid());
        $process->start();
        $this->assertIsInt($process->getPid());
    }
    public function testStart()
    {
        $process = new BackgroundProcess('echo started; sleep 3 ; echo completed', ['escape' => false]);

        $process->start();
        $this->assertTrue($process->isRunning());
        sleep(1);
   
        $this->assertStringContains('started', $process->getOutput());
        $this->assertStringNotContains('completed', $process->getOutput());
        $this->assertEquals(-1, $process->exitCode());
        
        sleep(3);
        $this->assertFalse($process->isRunning());
        $this->assertStringContains('started', $process->getOutput());
        $this->assertStringContains('completed', $process->getOutput());

        $this->assertEquals(0, $process->exitCode());
        $this->assertTrue($process->isSuccess());
    }

    public function testErrorStart()
    {
        $process = new BackgroundProcess(['cat','foo']);
        $process->start();
        $process->wait();
        $this->assertStringContains('cat: foo: No such file or directory', $process->getErrorOutput());
        $this->assertEquals(1, $process->exitCode());
        $this->assertFalse($process->isSuccess());
    }

    public function testErrorWait()
    {
        $process = new BackgroundProcess(['ls -la']);
        $this->expectException(LogicException::class);
        $process->wait();
    }

    public function testErrorStop()
    {
        $process = new BackgroundProcess(['ls -la']);
        $this->expectException(LogicException::class);
        $process->stop();
    }

    public function testErrorSuccess()
    {
        $process = new BackgroundProcess(['ls -la']);
        $this->expectException(LogicException::class);
        $process->isSuccess();
    }

    public function testErrorExitCode()
    {
        $process = new BackgroundProcess(['ls -la']);
        $this->assertNull($process->exitCode());
    }

    public function testErrorDirectory()
    {
        $this->expectException(RuntimeException::class);
        new BackgroundProcess(['ls -la'], ['directory' => '/foo/does-not-exist']);
    }

    public function testErrorStartStart()
    {
        $process = new BackgroundProcess('sleep 10');
        $process->start();
        $this->expectException(RuntimeException::class);
        $process->start();
    }

    /**
     * @depends testStart
     * @internal not sure how to get info from TTY, cant use output buffering etc
     * @return void
     */
    public function testOutput()
    {
        if (! posix_isatty(STDOUT)) {
            $this->markTestSkipped();
        }
        $process = new BackgroundProcess('echo started; sleep 1 ; echo completed', ['escape' => false,'output' => true]);
        $process->start();
        $this->assertTrue($process->isRunning());
    
        sleep(3);
        $this->assertFalse($process->isRunning());
        $this->assertStringNotContains('started', $process->getOutput());
        $this->assertStringNotContains('completed', $process->getOutput());

        $this->assertEquals(0, $process->exitCode());
    }

    public function testStartStop()
    {
        $process = new BackgroundProcess('echo started; sleep 10 ; echo completed', ['escape' => false]);
        $process->start();
        $this->assertTrue($process->isRunning());
        sleep(1);

        $this->assertStringContains('started', $process->getOutput());
        $this->assertStringNotContains('completed', $process->getOutput());
        $this->assertTrue($process->stop());
    
        sleep(1);
        $this->assertFalse($process->isRunning());
        $this->assertStringContains('started', $process->getOutput());
        $this->assertStringNotContains('completed', $process->getOutput());
    }

    public function testTimeout()
    {
        $process = new BackgroundProcess('echo started; sleep 10 ; echo completed', ['escape' => false,'timeout' => 3]);
        $process->start();
        $this->assertTrue($process->isRunning());
        $this->assertFalse($process->hasTimedOut());
        sleep(4);
        $this->assertTrue($process->isRunning());
        $this->assertTrue($process->hasTimedOut());
        $this->assertStringNotContains('completed', $process->getOutput());
    }

    public function testWait()
    {
        $process = new BackgroundProcess('echo started; sleep 3 ; echo completed', ['escape' => false]);
        $process->start();

        $this->assertTrue($process->isRunning());
        $this->assertStringNotContains('completed', $process->getOutput());
        $process->wait();
      
        $this->assertFalse($process->isRunning());
        $this->assertStringContains('completed', $process->getOutput());
    }

    public function testWaitUntil()
    {
        $process = new BackgroundProcess('echo started; sleep 2 ; echo ready; sleep 10; echo completed', ['escape' => false]);
        $process->start();

        $found = $process->waitUntil(function ($output, $error) {
            return strpos($output, 'ready') !== false;
        });
        $this->assertTrue($found);
      
        $this->assertStringContains('started', $process->getOutput());
        $this->assertStringContains('ready', $process->getOutput());
        $this->assertStringNotContains('completed', $process->getOutput());
    }
}
