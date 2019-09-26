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

namespace Origin\Test\Mailer;

use Origin\Mailer\Mailer;
use Origin\Mailer\MailerJob;
use Origin\TestSuite\TestTrait;
use Origin\TestSuite\OriginTestCase;

class AnotherDemoMailer extends Mailer
{
    use TestTrait;

    public $defaults = [
        'from' => 'no-reply@example.com',
    ];
    
    public function execute(array $params)
    {
        $this->first_name = $params['first_name'];

        $this->mail([
            'to' => $params['email'],
            'subject' => 'this is the subject message',
        ]);
    }
}

class MailerJobTest extends OriginTestCase
{
    public $fixtures = ['Origin.Queue'];

    /**
     * Test add to queue
     *
     * @return void
     */
    public function testDispatch()
    {
        $params = [
            'mailer' => new AnotherDemoMailer(),
            'arguments' => [
                [
                    'first_name' => 'jim',
                    'email' => 'demo@originphp.com',
                ]
            ],
        ];

        $this->assertTrue((new MailerJob())->dispatch($params));
    }

    /**
     * As there is template for this, this will call the on error and
     * as a result return false
     *
     * @return void
     */
    public function testThrowError()
    {
        $params = [
            'mailer' => new AnotherDemoMailer(),
            'arguments' => [
                [
                    'first_name' => 'jim',
                    'email' => 'demo@originphp.com',
                ]
            ],
        ];

        $this->assertFalse((new MailerJob())->dispatchNow($params));
    }
}
