<?php 
namespace App\Test\Fixture;

use Origin\TestSuite\Fixture;

class TagFixture extends Fixture
{
    public $schema = [
      'id' => [
        'type' => 'integer',
        'length' => 11,
        'default' => null,
        'null' => false,
        'key' => 'primary',
        'autoIncrement' => true,
      ],
      'title' =>  [
        'type' => 'string',
        'length' => 255,
        'default' => null,
        'null' => true,
      ],
      'created' => [
        'type' => 'datetime',
        'default' => null,
        'null' => false,
      ],
      'modified' => [
        'type' => 'datetime',
        'default' => null,
        'null' => false,
      ],
   ];
   
    public $records = [
        [
            'id' => 1000,
            'title' => 'New' ,
            'created'=>'2019-01-18 09:53:00',
            'modified'=>'2019-01-18 09:53:00'
        ],
        [
            'id' => 1001,
            'title' => 'Top Rated' ,
            'created'=>'2019-01-18 09:53:00',
            'modified'=>'2019-01-18 09:53:00'
        ],
    ];
}
