<?php
namespace App\Test\Controller;

use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\IntegrationTestTrait;
use Origin\Model\ModelRegistry;

class BookmarksControllerTest extends OriginTestCase
{
    use IntegrationTestTrait;

    /**
     * Load the Fixtures
     */
    public $fixtures = ['Bookmark','BookmarksTag','User'];

    public function setUp()
    {
        $user = [
            'id' => 1000,
            'name' => 'Frank' ,
            'email'=>'frank@example.com',
            'password'=>'secret',
            'dob'=>'1999-08-01',
            'created'=>'2019-01-18 09:53:00',
            'modified'=>'2019-01-18 09:53:00'];

        $this->session(['Auth.User' =>$user]);
    }

    public function testIndex()
    {
        $this->get('/bookmarks/index');
        $this->assertResponseOk();
        $this->assertResponseContains('<h2>Bookmarks</h2>');
    }

    public function testAdd()
    {
        $this->get('/bookmarks/add');
        $this->assertResponseOk();
        $this->assertResponseContains('<h2>Add Bookmark</h2>');
    }

    public function testAddPost()
    {
        $post = [
            'title' => 'Testing',
            'url' => 'https://github.com/originphp/framework/tree/master/origin/docs/development/testing.md',
            'tags' => 'draft,testing,PHPUnit',
            'category' => 'Development',
            'description' => 'Testing your OriginPHP applications'
        ];
        $this->post('/bookmarks/add', $post);
        $this->assertResponseSuccess();
        $this->assertRedirectContains('/bookmarks/view');
    }

    public function testEdit()
    {
        $this->get('/bookmarks/edit/1000');
        $this->assertResponseOk();
        $this->assertResponseContains('<h2>Edit Bookmark</h2>');
    }
    
    public function testEditPost()
    {
        $post = [
            'title' => 'OriginPHP - The Best PHP Framework',
            'url'=>'https://www.originphp.com',
            'category'=>'Development',
            'description'=>'The best PHP framework'
            ];
        $this->post('/bookmarks/edit/1000', $post);
        $this->assertResponseSuccess();
        $this->assertRedirect('/bookmarks/view/1000');

        $Bookmark = ModelRegistry::get('Bookmark');
        $bookmark = $Bookmark->get(1000);
        $this->assertEquals('OriginPHP - The Best PHP Framework', $bookmark->title);
    }

    public function testView()
    {
        $this->get('/bookmarks/view/1000');
        $this->assertResponseOk();
        
        $this->assertResponseContains('<h2>OriginPHP</h2>');

        $bookmark = $this->viewVariable('bookmark');
        $this->assertEquals('The best PHP framework', $bookmark->description);
    }

    public function testDelete()
    {
        $this->post('/bookmarks/delete/1000');
        $this->assertResponseSuccess();
        $this->assertRedirect('/bookmarks/index');

        $Bookmark = ModelRegistry::get('Bookmark');
        $count = $Bookmark->find('count', ['conditions'=>['id'=>1000]]);
        $this->assertEquals(0, $count);
    }
}
