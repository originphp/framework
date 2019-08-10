<?php
use Origin\Model\Seed;

class ApplicationSeed extends Seed
{
    public $bookmarks = [
        ['id' => 1000,'user_id' => 1000,'title' => 'Google','url' => 'https://www.google.com','description' => 'The most used search engine in the world.','category' => 'Search','created' => 'now()','modified' => 'now()'],
        ['id' => 1001,'user_id' => 1000,'title' => 'Bing','url' => 'https://www.bing.com','description' => 'Microsoft search engine','category' => 'Search','created' => 'now()','modified' => 'now()'],
        ['id' => 1002,'user_id' => 1000,'title' => 'OriginPHP','url' => 'https://www.originphp.com','description' => 'The PHP framework for rapidly building scalable web applications.','category' => 'Development','created' => 'now()','modified' => 'now()'],
    ];

    public $bookmarks_tags = [
        ['bookmark_id' => 1000,'tag_id' => 1002],
        ['bookmark_id' => 1001,'tag_id' => 1002],
        ['bookmark_id' => 1002,'tag_id' => 1000],
        ['bookmark_id' => 1002,'tag_id' => 1001],
    ];

    public $users = [
        ['id' => 1000,'name' => 'Demo User','email' => 'demo@example.com','password' => '$2y$10$/clqxdb.aWe43VXDUn8tA.yxKbWHZT3rN7gqITFaj32PZHI3.DkzW','dob' => '1999-12-28','created' => 'now()','modified' => 'now()'],
    ];

    public $tags = [
        ['id' => 1000,'title' => 'Framework','created' => 'now()','modified' => 'now()'],
        ['id' => 1001,'title' => 'PHP','created' => 'now()','modified' => 'now()'],
        ['id' => 1002,'title' => 'Search Engine','created' => 'now()','modified' => 'now()'],
    ];
}
