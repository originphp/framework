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

namespace Origin\Test\Fixture;

use Origin\TestSuite\Fixture;

class MigrationFixture extends Fixture
{
    public $schema = [
        'columns' => [
            'id' => ['type' => 'integer','autoIncrement' => true],
            'version' => [
                'type' => 'string',
                'limit' => 14,
                'default' => null,
                'null' => false,
            ],
            'rollback' => [
                'type' => 'text',
                'default' => null,
                'null' => true,
            ],
            'created' => [
                'type' => 'datetime',
                'default' => null,
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => ['type' => 'primary','column' => 'id'],
        ],
    ];
}
