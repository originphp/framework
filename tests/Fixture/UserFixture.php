<?php 
namespace App\Test\Fixture;

use Origin\TestSuite\Fixture;

class UserFixture extends Fixture
{
    public $schema = [
        'id' =>  [
        'type' => 'integer',
        'length' => 11,
        'default' => null,
        'null' => false,
        'key' => 'primary',
        'autoIncrement' => true,
        ],
        'name' => [
        'type' => 'string',
        'length' => 120,
        'null' => false,
    ],
        'email' => [
        'type' => 'string',
        'length' => 255,
        'null' => false,
    ],
        'password' =>[
        'type' => 'string',
        'length' => 255,
        'null' => false,
    ],
        'dob' =>  [
        'type' => 'date',
        'default' => null,
        'null' => true,
    ],
        'created' => [
        'type' => 'datetime',
        'null' => false,
    ],
        'modified' => [
        'type' => 'datetime',
        'null' => false,
    ]
   ];

    public $records = [
        ['id' => 1000,
        'name' => 'Frank' ,
        'email'=>'frank@example.com',
        'password'=>'secret',
        'dob'=>'1999-08-01',
        'created'=>'2019-01-18 09:53:00',
        'modified'=>'2019-01-18 09:53:00']
    ];
}
