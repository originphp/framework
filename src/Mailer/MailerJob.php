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
declare(strict_types = 1);
namespace Origin\Mailer;

use Origin\Job\Job;

class MailerJob extends Job
{
    protected $queue = 'mailers';

    protected function initialize(): void
    {
        $this->onError('errorHandler');
    }
    
    public function execute(array $params): void
    {
        $params['mailer']->dispatch(...$params['arguments']);
    }

    public function errorHandler(\Exception $exception): void
    {
        $this->retry(['wait' => '+30 minutes','limit' => 3]);
    }
}
