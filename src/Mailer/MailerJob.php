<?php
declare(strict_types = 1);
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
namespace Origin\Mailer;

use Origin\Job\Job;

class MailerJob extends Job
{
    public $queue = 'mailers';
    
    public function execute(array $params)
    {
        $params['mailer']->dispatch(...$params['arguments']);
    }

    public function onError(\Exception $exception)
    {
        $this->retry(['wait' => '+30 minutes','limit' => 3]);
    }
}
