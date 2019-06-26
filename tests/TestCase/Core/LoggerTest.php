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

namespace Origin\Test\Core;

use Origin\Core\Logger;

class LoggerTest extends \PHPUnit\Framework\TestCase
{
    private $filename = LOGS .DS . 'development.log';

    protected function setUp(): void
    {
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }
    /*public function expectMessage($channel,$level,$message){
        return '['.date('Y-m-d G:i:s') . '] ' . $channel . ' ' . strtoupper($level). ': ' .  $message;
    }*/

    public function testDebug()
    {
        $logger = new Logger('testDebug');
        $logger->debug('The lazy brown fox jumped over the wall');
        $this->assertContains('testDebug DEBUG: The lazy brown fox jumped over the wall', file_get_contents($this->filename));
        $logger->debug('The lazy brown fox {action} over the wall', ['action'=>'skipped']);
        $this->assertContains('testDebug DEBUG: The lazy brown fox skipped over the wall', file_get_contents($this->filename));
    }

    public function testInfo()
    {
        $logger = new Logger('testInfo');
        $logger->info('The lazy brown fox jumped over the wall');
        $this->assertContains('testInfo INFO: The lazy brown fox jumped over the wall', file_get_contents($this->filename));
        $logger->info('The lazy brown fox {action} over the wall', ['action'=>'skipped']);
        $this->assertContains('testInfo INFO: The lazy brown fox skipped over the wall', file_get_contents($this->filename));
    }

   
    public function testNotice()
    {
        $logger = new Logger('testNotice');
        $logger->notice('The lazy brown fox jumped over the wall');
        $this->assertContains('testNotice NOTICE: The lazy brown fox jumped over the wall', file_get_contents($this->filename));
        $logger->notice('The lazy brown fox {action} over the wall', ['action'=>'skipped']);
        $this->assertContains('testNotice NOTICE: The lazy brown fox skipped over the wall', file_get_contents($this->filename));
    }
    public function testWarning()
    {
        $logger = new Logger('testWarning');
        $logger->warning('The lazy brown fox jumped over the wall');
        $this->assertContains('testWarning WARNING: The lazy brown fox jumped over the wall', file_get_contents($this->filename));
        $logger->warning('The lazy brown fox {action} over the wall', ['action'=>'skipped']);
        $this->assertContains('testWarning WARNING: The lazy brown fox skipped over the wall', file_get_contents($this->filename));
    }
    public function testError()
    {
        $logger = new Logger('testError');
        $logger->error('The lazy brown fox jumped over the wall');
        $this->assertContains('testError ERROR: The lazy brown fox jumped over the wall', file_get_contents($this->filename));
        $logger->error('The lazy brown fox {action} over the wall', ['action'=>'skipped']);
        $this->assertContains('testError ERROR: The lazy brown fox skipped over the wall', file_get_contents($this->filename));
    }

    public function testCritical()
    {
        $logger = new Logger('testCritical');
        $logger->critical('The lazy brown fox jumped over the wall');
        $this->assertContains('testCritical CRITICAL: The lazy brown fox jumped over the wall', file_get_contents($this->filename));
        $logger->critical('The lazy brown fox {action} over the wall', ['action'=>'skipped']);
        $this->assertContains('testCritical CRITICAL: The lazy brown fox skipped over the wall', file_get_contents($this->filename));
    }

    public function testAlert()
    {
        $logger = new Logger('testAlert');
        $logger->alert('The lazy brown fox jumped over the wall');
        $this->assertContains('testAlert ALERT: The lazy brown fox jumped over the wall', file_get_contents($this->filename));
        $logger->alert('The lazy brown fox {action} over the wall', ['action'=>'skipped']);
        $this->assertContains('testAlert ALERT: The lazy brown fox skipped over the wall', file_get_contents($this->filename));
    }

    public function testEmergency()
    {
        $logger = new Logger('testEmergency');
        $logger->emergency('The lazy brown fox jumped over the wall');
        $this->assertContains('testEmergency EMERGENCY: The lazy brown fox jumped over the wall', file_get_contents($this->filename));
        $logger->emergency('The lazy brown fox {action} over the wall', ['action'=>'skipped']);
        $this->assertContains('testEmergency EMERGENCY: The lazy brown fox skipped over the wall', file_get_contents($this->filename));
    }
    public function testSetFilename()
    {
        $logger = new Logger('testFilename');
        $filename = sys_get_temp_dir() . DS . uniqid();
        $logger->filename($filename);
        $logger->debug('Something has gone Pete Tong');
        $this->assertContains('Something has gone Pete Tong', file_get_contents($filename));
    }
}
