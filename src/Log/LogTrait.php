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

namespace Origin\Log;

trait LogTrait
{
    /**
     * Easy access to logging.
     *
     * @internal in initial design, used the logger function. This is better reusable approach
     * @todo investigate adding this to controller,model,component,behavior,helper,command
     *
     * @param mixed $level Levels are
     *  - emergency: system is unusable
     *  - alert: action must be taken immediately
     *  - critical:critical conditions
     *  - error: error conditions
     *  - warning: warning conditions
     *  - notice: normal, but significant, condition
     *  - info: informational message
     *  - debug: debug-level message
     * @param string $message message and you can use placeholders {key}
     * @param array $context array with key value for placeholders
     * @return void
     */
    public function log(string $level, string $message, array $context = [])
    {
        Log::write($level, $message, $context);
    }
}
