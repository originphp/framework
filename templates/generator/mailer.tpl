<?php
namespace %namespace%\Mailer;

use App\Mailer\ApplicationMailer;
use Origin\Core\Config;
use Origin\Model\Entity;

class %class%Mailer extends ApplicationMailer
{
    protected function execute(Entity $user) : void
    {
        $this->user = $user;
        $this->url = Config::read('App.url');
        
        $this->mail([
            'to' => $user->email,
            'subject' => 'email subject'
        ]);
    }
}