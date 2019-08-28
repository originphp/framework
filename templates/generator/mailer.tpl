<?php
namespace %namespace%\Mailer;
use App\Mailer\AppMailer;

class %class%Mailer extends AppMailer
{
    public function execute(Entity $user)
    {
        $this->user = $user;
        
        $this->mail([
            'to' => $user->email,
            'subject' => 'email subject goes here',
        ]);
    }
}