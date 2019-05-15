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

use Origin\TestSuite\Fixture;
use Origin\Model\ConnectionManager;
use Origin\Model\Model;
use Origin\Exception\Exception;

class Movie extends Model
{
}
class MovieFixture extends Fixture
{
    public $schema = array(
        'id' => ['type' => 'primaryKey'],
        'name' => array(
          'type' => 'string',
          'length' => 255,
          'null' => false,
        ),
        'decsription' => 'text',
        'year' => array(
          'type' => 'integer',
          'default' => '0',
          'null' => false,
        ),
        'created' => 'datetime',
        'modified' => 'datetime',
    );

    public $records = [
        [
        'id'=>1,
        'name'=>'The Godfather',
        'year'=>1972,
        'created'=>'2019-02-10 08:17:00',
        'modified'=>'2019-02-10 08:17:00'
        ],
        [
        'id'=>2,
        'name'=>'One Flew Over the Cuckoo\'s Nest',
        'year'=>1975,
        'created'=>'2019-02-10 08:17:00',
        'modified'=>'2019-02-10 08:17:00'
        ],
        [
        'id'=>3,
        'name'=>'Forrest Gump',
        'year'=>1994,
        'created'=>'2019-02-10 08:17:00',
        'modified'=>'2019-02-10 08:17:00'
        ]
    ];
}
class FixtureTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $connection = ConnectionManager::get('test');
        $connection->execute("DROP TABLE IF EXISTS movies");
    }
    public function testConstruct()
    {
        $fixture = new MovieFixture();
        $this->assertEquals('movies', $fixture->table);
        $this->assertEquals('test', $fixture->datasource);
    }
    public function testCreate()
    {
        $fixture = new MovieFixture();
        $this->assertTrue($fixture->create());
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

        $model = new Movie();
        $model->datasource = 'test';
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

    /**
     * This will create a table called movies
     *
     * @return void
     */
    public function createTableForImporting()
    {
        $connection = ConnectionManager::get('default');

        // if existing datasource has movives then crash
        $connection->execute("DROP TABLE IF EXISTS movies"); // TMP
        $sql = $connection->createTable('movies',[
            'id' => 'primaryKey',
            'name' => ['type'=>'string','null'=>false],
            'description' => 'text',
            'year' => ['type'=>'integer','default'=>0,'null'=>false],
            'created' => 'datetime',
            'modified' => 'datetime'
        ]);
    
        $connection->execute($sql);
        $Movie = new Movie();
        $Movie->datasource = 'default';
        $entity = $Movie->new(['name'=>'The Sound of Music','year'=>1965]);
        return $Movie->save($entity);
    }
   
    public function testImportNull()
    {
        $fixture = new MovieFixture();

        $this->assertNull($fixture->import());
    }
    
    /**
     * Test unkown models - also dynamic models
     *
     * @return void
     */
    public function testImportDynamicModel()
    {
        $connection = ConnectionManager::get('default');
        $connection->execute("DROP TABLE IF EXISTS guests"); // TMP

        $fixture = new MovieFixture();
        $fixture->import = ['model'=>'Guest']; // convert to table
        
        $sql = $connection->createTable('guests',[
            'id' => 'primaryKey',
            'firstname' => ['type'=>'string','limit'=>'30']
        ]);
        
        //$sql = "CREATE TABLE guests (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,firstname VARCHAR(30) NOT NULL)";
        $connection->execute($sql);
        $this->assertTrue($fixture->import());
    }
    public function testImportModel()
    {
        $this->assertTrue($this->createTableForImporting());
        $fixture = new MovieFixture();
        $fixture->import = ['model'=>'Origin\Test\TestSuite\Movie'];
        $this->assertTrue($fixture->create());
    }

    public function testImportTable()
    {
        $this->assertTrue($this->createTableForImporting());
        $fixture = new MovieFixture();
        $fixture->import = ['table'=>'movies'];
        $this->assertTrue($fixture->create());
    }

    public function testImportNoTableException()
    {
        $this->assertTrue($this->createTableForImporting());
        $fixture = new MovieFixture();
        $fixture->import = ['foo'=>'bar'];
        $this->expectException(Exception::class);
        $fixture->create();
    }

    private function movieSql()
    {
        return "CREATE TABLE mmovies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            decsription TEXT,
            year INT DEFAULT 0 NOT NULL,
            created DATETIME,
            modified DATETIME
           )";
    }
}
