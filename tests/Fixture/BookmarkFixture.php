<?php 
namespace App\Test\Fixture;

use Origin\TestSuite\Fixture;

class BookmarkFixture extends Fixture
{
    public $import = ['model'=>'Bookmark'];

    public $records = [
        ['id' => 1000,
        'user_id' => 1000,
        'title' => 'OriginPHP' ,
        'url'=>'https://www.originphp.com',
        'category'=>'Development',
        'description'=>'The best PHP framework',
        'created'=>'2019-01-18 09:53:00',
        'modified'=>'2019-01-18 09:53:00']
    ];
}
