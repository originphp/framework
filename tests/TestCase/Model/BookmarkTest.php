<?php
namespace App\Test\TestCase\Model;

use Origin\TestSuite\OriginTestCase;
use Origin\Model\ModelRegistry;
use Origin\Model\Entity;

class BookmarkTest extends OriginTestCase
{
    public $fixtures = ['User','Bookmark'];

    public function setUp()
    {
        $this->Bookmark = ModelRegistry::get('Bookmark');
        parent::setUp();
    }

    public function testSave()
    {
        $bookmark = new Entity(
            [
                'title'=> 'OriginPHP',
                'user_id' => 1234,
                'url'=> 'https://www.originphp.com',
                'tags'=> 'new,framework',
                'category'=> 'New',
                'description'=> 'The best PHP framework'
            ]
        );
        
        $model = $this->getMockForModel('Bookmark', ['beforeSave']);
        $model->expects($this->once())
            ->method('beforeSave')
            ->will($this->returnValue(false));
    
        $result = $model->save($bookmark);
        $this->assertFalse($result);
    }

    public function tearDown()
    {
        parent::tearDown();
    }
}
