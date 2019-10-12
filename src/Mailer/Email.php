<?php

declare(strict_types=1);
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

use Origin\Core\StaticConfigTrait;
use Origin\Email\Email as SmtpEmail;

class Email
{
    use StaticConfigTrait;

    /**
     * Returns a configured Email object
     *
     * @param string $name
     * @return SmtpEmail
     */
    public static function account(string $name) : SmtpEmail
    {
        $config = static::config($name);
        if ($config) {
            return new SmtpEmail($config);
        }
        throw new InvalidArgumentException(sprintf('The email account `%s` does not exist.', $name));
    }
}
