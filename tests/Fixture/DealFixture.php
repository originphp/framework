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

class DealFixture extends Fixture
{
    public $schema = [
        'columns' => [
            'id' => ['type' => 'integer','autoIncrement' => true],
            'name' => [
                'type' => 'string',
                'limit' => 120,
                'default' => null,
                'null' => false,
            ],
            'amount' => [
                'type' => 'decimal',
                'default' => null,
                'null' => true,
                'precision' => '15',
                'scale' => 2,
            ],
            'close_date' => [
                'type' => 'date',
                'default' => null,
                'null' => true,
            ],
            'stage' => [
                'type' => 'string',
                'limit' => 150,
                'default' => null,
                'null' => false,
            ],
            'status' => [
                'type' => 'string',
                'limit' => 50,
                'default' => 'new',
                'null' => false,
            ],
            'description' => [
                'type' => 'text',
                'default' => null,
                'null' => true,
            ],
            'confirmed' => [
                'type' => 'time',
                'default' => null,
                'null' => true,
            ],
            'created' => [
                'type' => 'datetime',
                'default' => null,
                'null' => false,
            ],
            'modified' => [
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
