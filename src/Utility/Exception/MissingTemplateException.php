<?php
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
# Deprecated from v1.20
// @codeCoverageIgnoreStart
class_alias('Origin\Mailer\Exception\MissingTemplateException', 'Origin\Utility\Exception\MissingTemplateException');
deprecationWarning('Use Origin\Mailer\Exception\MissingTemplateException instead of Origin\Utility\Exception\MissingTemplateException.');
// @codeCoverageIgnoreEnd
