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

namespace Origin\Test\TestSuite;

use Origin\Model\Model;
use Origin\TestSuite\Fixture;
use Origin\Exception\Exception;
use Origin\Model\ConnectionManager;

class Movie extends Model
{
}

class MovieFixture extends Fixture
{
    protected $table = 'movies';

    protected $schema = [
        'columns' => [
            'id' => ['type' => 'integer','autoIncrement' => true],
            'name' => [
                'type' => 'string',
                'limit' => 255,
                'null' => false,
            ],
            'decsription' => 'text',
            'year' => [
                'type' => 'integer',
                'default' => '0',
                'null' => false,
            ],
            'created' => 'datetime',
            'modified' => 'datetime',
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary', 'column' => 'id',
            ],
        ],
        'indexes' => [
            'name_idx' => ['column' => 'name'],
        ],
    ];

    protected $records = [
        [
            'id' => 1,
            'name' => 'The Godfather',
            'year' => 1972,
            'created' => '2019-02-10 08:17:00',
            'modified' => '2019-02-10 08:17:00',
        ],
        [
            'id' => 2,
            'name' => 'One Flew Over the Cuckoo\'s Nest',
            'year' => 1975,
            'created' => '2019-02-10 08:17:00',
            'modified' => '2019-02-10 08:17:00',
        ],
        [
            'id' => 3,
            'name' => 'Forrest Gump',
            'year' => 1994,
            'created' => '2019-02-10 08:17:00',
            'modified' => '2019-02-10 08:17:00',
        ],
    ];

    public function table()
    {
        return $this->table;
    }
}

class FixtureTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS movies');
    }
    public function testConstruct()
    {
        $fixture = new MovieFixture();
        $this->assertEquals('movies', $fixture->table());
    }
    public function testCreate()
    {
        $fixture = new MovieFixture();
        $this->assertNull($fixture->create());
    }
    /**
     * @dpends testCreate
     *
     * @return void
     */
    public function testInsert()
    {
        $fixture = new MovieFixture();
        $fixture->create();
        $this->assertNull($fixture->insert());

        $model = new Movie(['connection'=>'test']);
       
        $this->assertEquals(3, $model->find('count'));
    }

    public function testTruncate()
    {
        $fixture = new MovieFixture();
        $fixture->create();
        $fixture->insert();
        $this->assertTrue($fixture->truncate());
    }
    public function testDrop()
    {
        $fixture = new MovieFixture();
        $fixture->create();
        $fixture->insert();
        $this->assertTrue($fixture->drop());
    }
}
