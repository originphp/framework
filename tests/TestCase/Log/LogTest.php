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
namespace Origin\Test\Log;

use Origin\Log\Log;
use Origin\Log\Engine\BaseEngine;
use Origin\Exception\InvalidArgumentException;

class NullEngine extends BaseEngine
{
    protected $data = null;

    public function log($level, $message, array $context = [])
    {
        $this->data = $this->format($level, $message, $context) . "\n";
    }
    
    public function getLog()
    {
        return $this->data;
    }
}

class LogTest extends \PHPUnit\Framework\TestCase
{
    protected $engine = null;
    protected function setUp() : void
    {
        Log::reset();
        Log::config('default', [
            'className' => 'Origin\Test\Log\NullEngine',
        ]);
    }
    public function testEmergency()
    {
        $date = date('Y-m-d G:i:s');
        Log::emergency('This is an emergency');
        $this->assertContains("[{$date}] application EMERGENCY: This is an emergency", Log::engine('default')->getLog());
       
        Log::emergency('This is an {value}', ['value' => 'emergency']);
        $this->assertContains("[{$date}] application EMERGENCY: This is an emergency", Log::engine('default')->getLog());
    }
    public function testAlert()
    {
        $date = date('Y-m-d G:i:s');
        Log::alert('Some system message');
        $this->assertContains("[{$date}] application ALERT: Some system message", Log::engine('default')->getLog());
       
        Log::alert('Some system message with the value:{value}', ['value' => 'not-important']);
        $this->assertContains("[{$date}] application ALERT: Some system message with the value:not-important", Log::engine('default')->getLog());
    }
    public function testCritical()
    {
        $date = date('Y-m-d G:i:s');
        Log::critical('This is critical');
        $this->assertContains("[{$date}] application CRITICAL: This is critical", Log::engine('default')->getLog());
       
        Log::critical('This is {value}', ['value' => 'critical']);
        $this->assertContains("[{$date}] application CRITICAL: This is critical", Log::engine('default')->getLog());
    }
    public function testError()
    {
        $date = date('Y-m-d G:i:s');
        Log::error('Some system message');
        $this->assertContains("[{$date}] application ERROR: Some system message", Log::engine('default')->getLog());
       
        Log::error('Some system message with the value:{value}', ['value' => 'not-important']);
        $this->assertContains("[{$date}] application ERROR: Some system message with the value:not-important", Log::engine('default')->getLog());
    }
    public function testWarning()
    {
        $date = date('Y-m-d G:i:s');
        Log::warning('Some system message');
        $this->assertContains("[{$date}] application WARNING: Some system message", Log::engine('default')->getLog());
       
        Log::warning('Some system message with the value:{value}', ['value' => 'not-important']);
        $this->assertContains("[{$date}] application WARNING: Some system message with the value:not-important", Log::engine('default')->getLog());
    }
    public function testNotice()
    {
        $date = date('Y-m-d G:i:s');
        Log::notice('Some system message');
        $this->assertContains("[{$date}] application NOTICE: Some system message", Log::engine('default')->getLog());
       
        Log::notice('Some system message with the value:{value}', ['value' => 'not-important']);
        $this->assertContains("[{$date}] application NOTICE: Some system message with the value:not-important", Log::engine('default')->getLog());
    }
    public function testInfo()
    {
        $date = date('Y-m-d G:i:s');
        Log::info('Some system message');
        $this->assertContains("[{$date}] application INFO: Some system message", Log::engine('default')->getLog());
       
        Log::info('Some system message with the value:{value}', ['value' => 'not-important']);
        $this->assertContains("[{$date}] application INFO: Some system message with the value:not-important", Log::engine('default')->getLog());
    }
    public function testDebug()
    {
        $date = date('Y-m-d G:i:s');
        Log::debug('Some system message');
        $this->assertContains("[{$date}] application DEBUG: Some system message", Log::engine('default')->getLog());
       
        Log::debug('Some system message with the value:{value}', ['value' => 'not-important']);
        $this->assertContains("[{$date}] application DEBUG: Some system message with the value:not-important", Log::engine('default')->getLog());
    }
    
    public function testChannel()
    {
        Log::debug('Some system message', ['channel' => 'custom']);
        $date = date('Y-m-d G:i:s');
        $this->assertContains("[{$date}] custom DEBUG: Some system message", Log::engine('default')->getLog());
    }

    public function testLevelsRestriction()
    {
        Log::config('default', [
            'className' => 'Origin\Test\Log\NullEngine',
            'levels' => ['critical'],
        ]);
        Log::debug('This will not be logged');
        $this->assertEmpty(Log::engine('default')->getLog());
        Log::critical('This will be logged');
        $this->assertContains('This will be logged', Log::engine('default')->getLog());
    }

    public function testChannelsRestriction()
    {
        Log::config('default', [
            'className' => 'Origin\Test\Log\NullEngine',
            'channels' => ['payments'],
        ]);
        Log::debug('This will not be logged', ['channel' => 'application']);
        $this->assertEmpty(Log::engine('default')->getLog());
        Log::critical('This will be logged', ['channel' => 'payments']);
        $this->assertContains('This will be logged', Log::engine('default')->getLog());
    }

    public function testCustomData()
    {
        Log::info('User registered', ['username' => 'pinkpotato','channel' => 'custom']);
        $date = date('Y-m-d G:i:s');
        $this->assertContains("[{$date}] custom INFO: User registered {\"username\":\"pinkpotato\"}", Log::engine('default')->getLog());
    }

    public function testInvalidClassName()
    {
        $this->expectException(InvalidArgumentException::class);
        Log::config('test', ['className' => 'Origin\DoesNotExist\FooEngine']);
        Log::debug('wont work');
    }
    
    public function testInvalidLogLevel()
    {
        $this->expectException(InvalidArgumentException::class);
        Log::write('informational', 'This is an invalid log level');
    }

    public function testInvalidEngine()
    {
        $this->expectException(InvalidArgumentException::class);
        Log::config('test', ['engine' => 'Foo']);
        Log::debug('wont work');
    }
    
    public function testInvalidEngine2()
    {
        Log::reset(); // force load engine
        $this->expectException(InvalidArgumentException::class);
        Log::engine('does-not-exist');
    }
}
