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

class UserFixture extends Fixture
{
    protected $schema = [
        'columns' => [
            'id' => ['type' => 'integer','autoIncrement' => true],
            'name' => [
                'type' => 'string',
                'limit' => 255,
                'null' => false,
            ],
            'email' => [
                'type' => 'string',
                'limit' => 255,
                'null' => false,
            ],
            'password' => [
                'type' => 'string',
                'limit' => 255,
                'null' => false,
            ],
            'api_token' => [
                'type' => 'string',
                'limit' => 40,
            ],
            'created' => 'datetime',
            'modified' => 'datetime',
        ],
        'constraints' => [
            'primary' => ['type' => 'primary','column' => 'id'],
        ],
    
    ];
    protected $records = [
        [
            'id' => 1000,
            'name' => 'James',
            'email' => 'james@example.com',
            'password' => '$2y$10$V5RgkqQ6Onnxgz2rmEBJDuftS9DX7iD0qv8V3LlM0qDdTYK2Y3Fbq',
            'api_token' => '43cbd312fd6eaf3480a4572aa988ada0f4c6310b',
            'created' => '2018-12-20 09:00:00',
            'created' => '2018-12-20 09:00:15',
        ],
        [
            'id' => 1001,
            'name' => 'Amanda',
            'email' => 'amanda@example.com',
            'password' => '$2y$10$YK3SO6y4O9ObgpLG6HH75e6o2uQFQxdQ3qbE8szwMCTpZxSao6H16',
            'api_token' => 'dea50af153b77b3f3b725517ba18b5f0619fa4da',
            'created' => '2018-12-20 09:01:00',
            'created' => '2018-12-20 09:00:30',
        ],
    ];
}
