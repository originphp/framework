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

namespace Origin\Test\Mailbox;

use Origin\Mailbox\Server;
use Origin\TestSuite\OriginTestCase;
use Origin\Mailbox\Model\InboundEmail;

class MockServer extends Server
{
    protected $stream = 'php://temp';

    public function __construct(string $message)
    {
        $tmp = tempnam(sys_get_temp_dir(), '');
        file_put_contents($tmp, $message);
        $this->stream = $tmp;
    }
}

class ServerTest extends OriginTestCase
{
    public $fixtures = ['Origin.Mailbox','Origin.Queue'];

    /**
     * @var \Origin\Mailbox\Model\InboundEmail
     */
    protected $InboundEmail;

    protected function initialize(): void
    {
        $this->InboundEmail = $this->loadModel('InboundEmail', [
            'className' => InboundEmail::class
        ]);
    }

    public function testDispatch()
    {
        $message = file_get_contents(__DIR__  . '/messages/text.eml');
        $server = new MockServer($message);
  
        /**
         * Test dispatch process and save to DB
         */
        $this->assertTrue($server->dispatch());

        /**
         * Ensure same message cannot be submitted twice
         */
        $this->assertFalse($server->dispatch());
    }

    public function testDispatchMaintenanceMode()
    {
        // clear mailbox directory
        $files = scandir(tmp_path('mailbox'));
        foreach ($files as $file) {
            if (! in_array($file, ['.','..'])) {
                unlink(tmp_path('mailbox/' . $file));
            }
        }

        // enable maintenance mode
        file_put_contents(tmp_path('maintenance.json'), json_encode([]));

        // start test
        $message = file_get_contents(__DIR__  . '/messages/text.eml');
        $server = new MockServer($message);
        $this->assertEquals(1, $this->InboundEmail->count());
        
        /**
         * Test dispatch process and save to HD
         */
        $this->assertTrue($server->dispatch());
        @unlink(tmp_path('maintenance.json'));

        // Test not in maintencemode
        $message = file_get_contents(__DIR__  . '/messages/html.eml');
        $server = new MockServer($message);
        $this->assertTrue($server->dispatch());
        $this->assertEquals(3, $this->InboundEmail->count());
    }
}
