<?php
namespace %namespace%\Test\Mailer;

use Origin\TestSuite\OriginTestCase;
use App\Mailer\%class%Mailer;

class %class%MailerTest extends OriginTestCase
{
    public function startup()
    {
        parent::startup(); // remember parent
        $this->loadModel('User');
    }
    
    public function testExecute()
    {
        $user = $this->User->find('first', ['conditions' => ['id' => 1000]]);
        $message = (new %class%Mailer())->dispatch($user);
        $this->assertContains('To: user@example.com',$message->header());
        $this->assertContains('From: user@example.com',$message->header());
        $this->assertContains('Hello user',$message->body());
    }
}