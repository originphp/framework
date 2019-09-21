<?php
namespace %namespace%\Mailer;

use App\Mailer\ApplicationMailer;
use Origin\Core\Config;
use Origin\Model\Entity;

class %class%Mailer extends ApplicationMailer
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