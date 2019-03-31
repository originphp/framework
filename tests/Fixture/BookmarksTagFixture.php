<?php 
namespace App\Test\Fixture;

use Origin\TestSuite\Fixture;

class BookmarksTagFixture extends Fixture
{
    public $schema = [
        'bookmark_id' => [
            'type' => 'integer',
            'length' => 11,
            'default' => null,
            'null' => false,
            'key' => 'primary',
        ],
        'tag_id' =>  [
            'type' => 'integer',
            'length' => 11,
            'default' => null,
            'null' => false,
            'key' => 'primary'
        ]
    ];

    public $records = [
        [
            'bookmark_id' => 1000,
            'tag_id' => 1000,
        ],
        [
            'bookmark_id' => 1000,
            'tag_id' => 1001,
        ],

    ];
}
