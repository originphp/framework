<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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
namespace Origin\Publisher;

use Origin\Job\Job;

class ListenerJob extends Job
{
    protected $queue = 'listeners';

    protected function initialize(): void
    {
        $this->onError('errorHandler');
    }

    protected function execute(string $className, string $method, array $args = [])
    {
        ( new Publisher())->dispatch(new $className(), $method, $args);
    }

    protected function errorHandler(\Exception $exception): void
    {
        $this->retry();
    }
}
