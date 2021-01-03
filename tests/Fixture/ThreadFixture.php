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

namespace Origin\Test\Fixture;

use Origin\TestSuite\Fixture;

class ThreadFixture extends Fixture
{
    protected $schema = [
        'columns' => [
            'id' => ['type' => 'integer','autoIncrement' => true],
            'name' => ['type' => 'string', 'limit' => 255, 'null' => false],
            'views' => ['type' => 'integer'],
        ],
        'constraints' => [
            'primary' => ['type' => 'primary','column' => 'id'],
        ],
    ];
    protected $records = [
        [
            'id' => 1000,
            'name' => 'Foo',
            'views' => 0,
        ],
    ];
}
