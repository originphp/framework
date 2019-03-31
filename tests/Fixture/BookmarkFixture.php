<?php 
namespace App\Test\Fixture;

use Origin\TestSuite\Fixture;

class BookmarkFixture extends Fixture
{
    public $schema = [
        'id' =>
        [
          'type' => 'integer',
          'length' => 11,
          'default' => null,
          'null' => false,
          'key' => 'primary',
          'autoIncrement' => true,
        ],
        'user_id' =>  [
          'type' => 'integer',
          'length' => 11,
          'default' => null,
          'null' => false,
        ],
        'title' => [
          'type' => 'string',
          'length' => 50,
          'default' => '',
          'null' => false,
        ],
        'description' => [
          'type' => 'text',
          'default' => null,
          'null' => true,
        ],
        'url' => [
          'type' => 'text',
          'default' => null,
          'null' => true,
        ],
        'category' =>  [
          'type' => 'string',
          'length' => 80,
          'default' => null,
          'null' => true,
        ],
         'created' =>  [
          'type' => 'datetime',
          'default' => null,
          'null' => false,
         ],
         'modified' => [
          'type' => 'datetime',
          'default' => null,
          'null' => false,
        ]
        ];
    /**
     * Fixture will create these records
     *
     * @var array
     */
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
