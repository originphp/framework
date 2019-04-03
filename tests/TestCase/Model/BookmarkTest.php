<?php
namespace App\Test\Model;

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

    public function tearDown()
    {
        parent::tearDown();
    }
}
