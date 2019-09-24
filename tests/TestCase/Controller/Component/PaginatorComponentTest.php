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

use Origin\Model\Model;
use Origin\Http\Request; // callMethod + getProperty
use Origin\Http\Response;
use Origin\Model\ModelRegistry;
use Origin\TestSuite\TestTrait;
use Origin\Controller\Controller;
use Origin\Model\ConnectionManager;
use Origin\Http\Exception\NotFoundException;
use Origin\Controller\Component\PaginatorComponent;

class Pet extends Model
{
    public $connection = 'test';
}

class Owner extends Model
{
    public $connection = 'test';
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
 * @todo i think this test can be written associatedout database access mocking fetchResults and check settings array is correct
 */
class PaginatorComponentTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->Controller = new PetsController(new Request('pets/index'), new Response());
        $this->PaginatorComponent = new PaginatorComponent($this->Controller);
      
        $connection = ConnectionManager::get('test');
        $sql = $connection->adapter()->createTable('pets', [
            'id' => ['type' => 'primaryKey'],
            'owner_id' => ['type' => 'int','null' => false],
            'name' => ['type' => 'string','limit' => 20],
        ]);
        $connection->execute($sql);

        $sql = $connection->adapter()->createTable('owners', [
            'id' => ['type' => 'primaryKey'],
            'name' => ['type' => 'string','limit' => 20],
        ]);
        $connection->execute($sql);
        
        # Create Dummy Data
        $this->Pet = new Pet();
      
        ModelRegistry::set('Pet', $this->Pet);

        for ($i = 0;$i < 100;$i++) {
            $this->Pet->save($this->Pet->new(['owner_id' => $i + 1000, 'name' => 'Pet' . $i]));
        }
    }

    protected function tearDown(): void
    {
        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS pets');
        $connection->execute('DROP TABLE IF EXISTS owners');
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

        $results = $this->PaginatorComponent->paginate($this->Pet, ['sort' => 'non_existant']);
        $this->assertEquals(20, count($results));

        // Test foreign_keys
        $results = $this->PaginatorComponent->paginate($this->Pet, ['sort' => 'owner_id']);
        $this->assertEquals(20, count($results));

        // Test foreign_keys
        $results = $this->PaginatorComponent->paginate($this->Pet, ['sort' => 'id','direction' => 'ASC']);
        $this->assertEquals(1, $results[0]->id);
    
        $results = $this->PaginatorComponent->paginate($this->Pet, ['sort' => 'id','direction' => 'DESC']);
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
        $results = $this->PaginatorComponent->paginate($this->Pet, ['page' => 10000]);
    }

    /**
     * An xss attack to get mysql to generate an error ?page='somestring'
     *
     * @return void
     */
    public function testPaginateSecurity()
    {
        $this->expectException(NotFoundException::class);
        $this->PaginatorComponent->paginate($this->Pet, ['page' => 'abc']);
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
        $results = $this->PaginatorComponent->paginate($Pet, ['sort' => 'owner_id','associated' => ['Owner']]);
        $this->assertEquals(20, count($results));

        $results = $PaginatorComponent->paginate($Pet, ['sort' => 'owner_id','associated' => ['Owner']]);
        $this->assertEquals('asc', $results['order']['owners.name']); // check alias.

        $Pet = new Pet();
        $Pet->belongsTo('MyOwner', ['foreignKey' => 'owner_id']);
        $Pet->MyOwner = new Model(['name' => 'MyOwner','alias' => 'MyOwner','table' => 'owners','connection' => 'test']);

        $results = $this->PaginatorComponent->paginate($Pet, ['sort' => 'owner_id','associated' => ['MyOwner']]);
        $this->assertEquals(20, count($results));
    }
}
