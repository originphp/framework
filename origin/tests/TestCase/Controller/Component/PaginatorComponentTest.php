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

namespace Origin\Test\Controller\Component;

use Origin\Controller\Component\PaginatorComponent;
use Origin\TestSuite\TestTrait; // callMethod + getProperty
use Origin\Controller\Controller;
use Origin\Controller\Request;
use Origin\Controller\Response;

use Origin\Model\Model;
use Origin\Model\ModelRegistry;
use Origin\Model\ConnectionManager;
use Origin\Exception\NotFoundException;

class Pet extends Model
{
    public $datasource = 'test';
}

class Owner extends Model
{
    public $datasource = 'test';
}

class PetsController extends Controller
{
    public $autoRender = false;
    public function index()
    {
    }
}

class PaginatorControllerTest extends Controller
{
    use TestTrait;
}

class MockPaginatorComponent extends PaginatorComponent
{
    public function fetchResults(array $settings)
    {
        return $settings;
    }
}


/**
 * @todo i think this test can be written without database access mocking fetchResults and check settings array is correct
 */
class PaginatorComponentTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->Controller = new PetsController(new Request('pets/index'), new Response());
        $this->PaginatorComponent = new PaginatorComponent($this->Controller);
      
        
        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS pets,owners');
        $connection->execute('CREATE TABLE IF NOT EXISTS pets ( id INT AUTO_INCREMENT PRIMARY KEY, owner_id INT NOT NULL,name VARCHAR(20));');
        $connection->execute('CREATE TABLE IF NOT EXISTS owners ( id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(20));');
        
        # Create Dummy Data
        $this->Pet = new Pet();
      
        ModelRegistry::set('Pet', $this->Pet);

        for ($i=0;$i<100;$i++) {
            $this->Pet->save($this->Pet->newEntity(['owner_id' => $i + 1000, 'name'=>'Pet' . $i]));
        }
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function testPaginate()
    {
        $results = $this->PaginatorComponent->paginate($this->Pet);
        $this->assertEquals(20, count($results));

        $results = $this->PaginatorComponent->paginate($this->Pet, ['sort'=>'non_existant']);
        $this->assertEquals(20, count($results));

        // Test foreign_keys
        $results = $this->PaginatorComponent->paginate($this->Pet, ['sort'=>'owner_id']);
        $this->assertEquals(20, count($results));

        // Test foreign_keys
        $results = $this->PaginatorComponent->paginate($this->Pet, ['sort'=>'id','direction'=>'ASC']);
        $this->assertEquals(1, $results[0]->id);
    
        $results = $this->PaginatorComponent->paginate($this->Pet, ['sort'=>'id','direction'=>'DESC']);
        $this->assertEquals(100, $results[0]->id);

        // test url sorting
        $this->Controller = new PetsController(new Request('pets/index?sort=name&direction=desc'), new Response());
        $PaginatorComponent = new PaginatorComponent($this->Controller);
        $results = $PaginatorComponent->paginate($this->Pet);
        $this->assertEquals('Pet99', $results[0]->name);

        $this->Controller = new PetsController(new Request('pets/index?sort=name&direction=asc'), new Response());
        $PaginatorComponent = new PaginatorComponent($this->Controller);
        $results = $PaginatorComponent->paginate($this->Pet);
        $this->assertEquals('Pet0', $results[0]->name);

        $this->expectException(NotFoundException::class);
        $results = $this->PaginatorComponent->paginate($this->Pet, ['page'=>10000]);
    }

    /**
     * Because owner model is loaded, it will start doing magic. This test
     * is to reach some deeper code.
     */
    public function testPaginateSortForeignKey()
    {
        $PaginatorComponent = new MockPaginatorComponent($this->Controller); // disable find
        $Pet = $this->Pet;
        $Pet->belongsTo('Owner');
        $Pet->Owner = new Owner();

        // Test foreign_keys
        $results = $this->PaginatorComponent->paginate($Pet, ['sort'=>'owner_id','contain'=>['Owner']]);
        $this->assertEquals(20, count($results));

        $results = $PaginatorComponent->paginate($Pet, ['sort'=>'owner_id','contain'=>['Owner']]);
        $this->assertEquals('asc', $results['order']['Owner.name']); // check alias.

        $Pet = new Pet();
        $Pet->belongsTo('MyOwner', ['foreignKey'=>'owner_id']);
        $Pet->MyOwner = new Model(['name'=>'MyOwner','alias'=>'MyOwner','table'=>'owners','datasource'=>'test']);

        $results = $this->PaginatorComponent->paginate($Pet, ['sort'=>'owner_id','contain'=>['MyOwner']]);
        $this->assertEquals(20, count($results));
    }
}
