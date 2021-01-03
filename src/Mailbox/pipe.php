#!/usr/bin/php -q
<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */
/**
 * # Instructions
 *
 * 1. path to php is correct (above)
 * 2. permissions chmod a+x pipe.php
 *
 * # Testing:
 *
 * cat tests/email.txt | ./pipe.php
 */
declare(strict_types=1);
namespace Origin\Mailbox;

require dirname(__DIR__, 5) .  '/config/bootstrap.php';

(new Server())->dispatch();
