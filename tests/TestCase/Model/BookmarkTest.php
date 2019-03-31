<?php
namespace App\Test\TestCase\Model;

use Origin\TestSuite\OriginTestCase;
use Origin\Model\ModelRegistry;

/**
 * @property \App\Model\Bookmark $Bookmark
 */
class BookmarkTest extends OriginTestCase
{
    public $fixtures = [
        'Bookmark','BookmarksTag','Tag','User'
    ];

    public function setUp()
    {
        $this->Bookmark = ModelRegistry::get('Bookmark');
        parent::setUp();
    }

    public function testTagsToString()
    {
        $bookmark = $this->Bookmark->get(1000, ['associated'=>['Tag']]);
        $this->assertEquals('New,Top Rated', $bookmark->tag_string);
    }

    public function testStringToTags()
    {
        $bookmark = $this->Bookmark->new([
            'title' => 'Google',
            'url' =>'https://www.google.com',
             'category' => 'Test',
            'tag_string' => 'Search Engine,Best',
            'user_id' => 1000
        ]);
     
        $this->assertTrue($this->Bookmark->save($bookmark));
        $this->assertEquals('Search Engine', $bookmark->tags[0]->title);
        $this->assertEquals(1002, $bookmark->tags[0]->id);
        $this->assertEquals('Best', $bookmark->tags[1]->title);
        $this->assertEquals(1003, $bookmark->tags[1]->id);
    }

    public function xxtestSave()
    {
        $bookmark = $this->Bookmark->new(
            [
                'title'=> 'OriginPHP',
                'user_id' => 1234,
                'url'=> 'https://www.originphp.com',
                'tag_string'=> 'new,framework',
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
