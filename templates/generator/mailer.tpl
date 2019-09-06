<?php
namespace %namespace%\Mailer;

use App\Mailer\AppMailer;
use Origin\Core\Config;
use Origin\Model\Entity;

class %class%Mailer extends AppMailer
{
    public function execute(Entity $user)
    {
        $this->user = $user;
        $this->url = Config::read('App.url');
        
        $this->mail([
            'to' => $user->email,
            'subject' => 'email subject'
        ]);
    }
}