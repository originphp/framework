<?php
namespace Origin\Test\Command;

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
    public function onException(\Exception $exception)
    {
        parent::onException($exception);
        $this->retry(['wait' => 'now','limit' => 1]);
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
        (new PassOrFailJob(true))->dispatch(['queu']);
        $this->exec('queue:worker --connection=test');
        $this->assertExitSuccess();
        $this->assertOutputContains('<cyan>Run</cyan> <text>PassOrFail</text> <green>1000</green><text> (0s)</text> <pass> OK </pass>');
    }

    public function testRunJobFail()
    {
        // Create a job and dispatch
        (new PassOrFailJob(false))->dispatch();
        $this->exec('queue:worker --connection=test');
        $this->assertExitSuccess();
        $this->assertOutputContains('<cyan>Run</cyan> <text>PassOrFail</text> <green>1000</green><text> (0s)</text> <fail> FAILED </fail>');
    }

    public function testRunJobFailRetry()
    {
        // Create a job and dispatch
        (new PassOrFailJob(false))->dispatch();
        $this->exec('queue:worker --connection=test');
        $this->assertExitSuccess();
        $this->assertOutputContains('<cyan>Run</cyan> <text>PassOrFail</text> <green>1000</green><text> (0s)</text> <fail> FAILED </fail>');
        $this->assertOutputNotContains('Retry');

        // Second time should be retry
        $this->exec('queue:worker --connection=test');
        $this->assertOutputNotContains('<cyan>Run</cyan> <text>PassOrFail</text> <green>1000</green><text> (0s)</text> <fail> FAILED </fail>');
        $this->assertExitSuccess();
        $this->assertOutputContains('<cyan>Retry #1</cyan> <text>PassOrFail</text> <green>1000</green><text> (0s)</text> <fail> FAILED </fail>');
    }
}
