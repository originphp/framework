<?php
namespace Origin\Test\Console\Command;

use Origin\Job\Job;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class PassOrFailJob extends Job
{
    public $connection = 'test';

    public function execute(bool $pass = true)
    {
        if (! $pass) {
            $a = 1 / 0;
        }
    }
    public function onError(\Exception $exception)
    {
        $this->retry(['wait' => '+1 second','limit' => 1]);
    }
}

class QueueWorkerCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;
    
    public $fixtures = ['Origin.Queue'];

    public function testRunNothingInQueue()
    {
        $this->exec('queue:worker --connection=test');
        $this->assertExitSuccess();
    }

    public function testRunJobSuccessful()
    {
        // Create a job and dispatch
        (new PassOrFailJob())->dispatch(true);
        $this->exec('queue:worker --connection=test');
        
        $this->assertExitSuccess();
        $this->assertOutputContains('<cyan>Run</cyan> <text>PassOrFail</text>');
        $this->assertOutputContains('<pass> OK </pass>');
    }

    public function testRunJobFail()
    {
        // Create a job and dispatch
        (new PassOrFailJob())->dispatch(false);
        $this->exec('queue:worker --connection=test');
        $this->assertExitSuccess();
        $this->assertOutputContains('<cyan>Run</cyan> <text>PassOrFail</text>');
        $this->assertOutputContains('<fail> FAILED </fail>');
    }

    public function testRunJobFailRetry()
    {
        // Create a job and dispatch
        (new PassOrFailJob())->dispatch(false);
        $this->exec('queue:worker --connection=test');
      
        $this->assertExitSuccess();
        $this->assertOutputContains('<cyan>Run</cyan> <text>PassOrFail</text>');
        $this->assertOutputContains('<fail> FAILED </fail>');
        $this->assertOutputNotContains('Retry');

        sleep(1);

        // Second time should be retry
        $this->exec('queue:worker --connection=test');
        $this->assertExitSuccess();
        
        $this->assertOutputNotContains('<cyan>Run</cyan> <text>PassOrFail</text>');
        $this->assertOutputContains('<cyan>Retry #1</cyan> <text>PassOrFail</text>');
        $this->assertOutputContains('<fail> FAILED </fail>');
    }
}
