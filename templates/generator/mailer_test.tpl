<?php
namespace %namespace%\Test\Mailer;

use Origin\TestSuite\OriginTestCase;
use App\Mailer\%class%Mailer;

class %class%MailerTest extends OriginTestCase
{
    public function testExecute()
    {
        $message = (new %class%Mailer())->dispatch();
        $this->assertContains('To: user@example.com',$message->header());
        $this->assertContains('From: user@example.com',$message->header());
        $this->assertContains('Hello user',$message->body());
    }
}