<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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

    protected $defaults = [
        'from' => 'no-reply@example.com',
    ];
    
    public function execute(array $params): void
    {
        $this->first_name = $params['first_name'];

        $this->mail([
            'to' => $params['email'],
            'subject' => 'this is the subject message',
            'body' => $params['html'],
            'contentType' => 'html'
        ]);
    }
    /**
     * Returns the options set by mail
     *
     * @return void
     */
    public function options()
    {
        return $this->options;
    }
}

class MailerJobTest extends OriginTestCase
{
    protected $fixtures = ['Origin.Queue'];

    /**
     * Test add to queue
     *
     * @deprecated this will be removed in future
     *
     * @return void
     */
    public function testDispatchBackwardsComptability()
    {
        $mailer = new AnotherDemoMailer();
        $params = [
            'mailer' => $mailer,
            'arguments' => [
                [
                    'first_name' => 'jim',
                    'email' => 'demo@originphp.com',
                    'html' => '<p>This is a test </p>'
                ]
            ],
        ];

        $this->assertTrue((new MailerJob())->dispatch($params));
    }

    /**
    * Test add to queue
    *
    * @deprecated this will be removed in future
    *
    * @return void
    */
    public function testDispatchObject()
    {
        $data = [
            'first_name' => 'jim',
            'email' => 'demo@originphp.com',
            'html' => '<p>This is a test </p>'
        ];
        $this->assertTrue((new MailerJob())->dispatch(new AnotherDemoMailer(), $data));
    }

    /**
     * Test add to queue
     *
     * @deprecated this will be removed in future
     *
     * @return void
     */
    public function testDispatch()
    {
        $data = [
            'first_name' => 'jim',
            'email' => 'demo@originphp.com',
            'html' => '<p>This is a test </p>'
        ];

        $this->assertTrue((new MailerJob())->dispatch(AnotherDemoMailer::class, $data));
    }

    public function testDispatchNow()
    {
        $mailer = new AnotherDemoMailer();
        $data = [
            'first_name' => 'jim',
            'email' => 'demo@originphp.com',
            'html' => '<p>This is a test </p>'
        ];

        $this->assertTrue((new MailerJob())->dispatchNow($mailer, $data));
        $this->assertEquals('this is the subject message', $mailer->options()['subject']);
    }

    /**
     * This is fail cause of missing template exception
     *
     * @return void
     */
    public function testDispatchThrowError()
    {
        $params = [
            'mailer' => new AnotherDemoMailer(),
            'arguments' => [
                [
                    'first_name' => 'jim',
                    'email' => 'demo@originphp.com',
                    'html' => null
                ]
            ],
        ];

        $this->assertFalse((new MailerJob())->dispatchNow($params));
    }
}
