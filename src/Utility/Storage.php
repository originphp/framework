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
class_alias('Origin\Storage\Storage', 'Origin\Utility\Storage');
deprecationWarning('Use Origin\Storage\Storage instead of Origin\Utility\Storage.');
// @codeCoverageIgnoreEnd
