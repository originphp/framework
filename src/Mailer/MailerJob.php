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
    
    /**
     * Executes the MailerJob, the first param is class or object and then after that are arguments
     *
     * @return void
     */
    public function execute(): void
    {
        $arguments = func_get_args();

        /**
         * Temporary backwards comptability to prevent queued jobs from breaking
         * @deprecated this will be depcreated
         */
        if (isset($arguments[0]) && is_array($arguments[0])) {
            $mailer = $arguments['mailer'];
            $arguments = $arguments['arguments'];
        } else {
            $mailer = array_shift($arguments);
            if (! is_object($mailer)) {
                $mailer = new $mailer();
            }
        }
     
        $mailer->dispatch(...$arguments);
    }

    public function errorHandler(\Exception $exception): void
    {
        $this->retry(['wait' => '+30 minutes','limit' => 3]);
    }
}
