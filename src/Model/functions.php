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

use Origin\Model\Model;
use Origin\Model\ModelRegistry;
use Origin\Model\Exception\MissingModelException;

/**
 * Loads a model, uses from registry or creates a new one.
 * This is an internal function and subject to change.
 *
 * @param string $model User, MyPlugin.User, User::class
 * @param array $options
 * @return \Origin\Model\Model
 */
function modelRegistryGet(string $model, array $options = []): Model
{
    $object = ModelRegistry::get($model, $options);
    if (! $object) {
        throw new MissingModelException($model);
    }

    return $object;
}
