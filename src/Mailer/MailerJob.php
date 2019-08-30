<?php
namespace Origin\Mailer;

use Origin\Job\Job;

class MailerJob extends Job
{
    public function execute(array $params)
    {
        $params['mailer']->dispatch(...$params['arguments']);
    }

    public function onError(\Exception $exception)
    {
        $this->retry(['wait' => '+30 minutes','limit' => 3]);
    }
}
