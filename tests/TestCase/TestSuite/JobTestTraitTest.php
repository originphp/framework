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

namespace Origin\Test\TestSuite;

use Origin\Mailer\Mailer;
use Origin\Mailer\MailerJob;
use Origin\TestSuite\JobTestTrait;
use Origin\TestSuite\OriginTestCase;

class DummyMailer extends Mailer
{
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
}

class JobTestTraitTest extends OriginTestCase
{
    use JobTestTrait;
    protected $fixtures = ['Origin.Queue'];
    
    public function testGet()
    {
        $data = [
            'first_name' => 'jim',
            'email' => 'demo@originphp.com',
            'html' => '<p>This is a test </p>'
        ];

        $this->assertNoEnqueuedJobs();
        $this->assertNoEnqueuedJobs('mailers');
        $this->assertTrue((new MailerJob())->dispatch(DummyMailer::class, $data));
        $this->assertEnqueuedJobs(1);
        $this->assertEnqueuedJobs(1, 'mailers');

        $this->assertJobEnqueued(MailerJob::class);
        $this->assertJobEnqueued(MailerJob::class, 'mailers');
        $this->assertJobEnqueuedWith(MailerJob::class, [DummyMailer::class,$data]);
        $this->assertJobEnqueuedWith(MailerJob::class, [DummyMailer::class,$data], 'mailers');

        $this->assertEquals(1, $this->runEnqueuedJobs('mailers'));
        $this->assertEquals(0, $this->runEnqueuedJobs('mailers'));

        // test job avilable but on in queue that we want
        $this->assertTrue((new MailerJob())->dispatch(DummyMailer::class, $data));
        $this->assertEquals(0, $this->runEnqueuedJobs('foo'));
    }
}
