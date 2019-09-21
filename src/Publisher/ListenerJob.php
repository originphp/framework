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
namespace Origin\Publisher;

use Origin\Job\Job;

class ListenerJob extends Job
{
    public $queue = 'listeners';

    public function execute(string $className, string $method, array $args = [])
    {
        ( new Publisher())->dispatch(new $className(), $method, $args);
    }

    public function onError(\Exception $exception)
    {
        $this->retry();
    }
}
