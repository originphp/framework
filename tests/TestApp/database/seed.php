<?php
use Origin\Model\Seed;

class ApplicationSeed extends Seed
{
    protected $bookmarks = [
        ['id' => 1000,'user_id' => 1000,'title' => 'Google','url' => 'https://www.google.com','description' => 'The most used search engine in the world.','category' => 'Search','created' => '2019-08-10 13:18:12','modified' => '2019-08-10 13:18:12'],
        ['id' => 1001,'user_id' => 1000,'title' => 'Bing','url' => 'https://www.bing.com','description' => 'Microsoft search engine','category' => 'Search','created' => '2019-08-10 13:18:12','modified' => '2019-08-10 13:18:12'],
        ['id' => 1002,'user_id' => 1000,'title' => 'OriginPHP','url' => 'https://www.originphp.com','description' => 'The PHP framework for rapidly building scalable web applications.','category' => 'Development','created' => '2019-08-10 13:18:12','modified' => '2019-08-10 13:18:12'],
    ];

    protected $bookmarks_tags = [
        ['bookmark_id' => 1000,'tag_id' => 1002],
        ['bookmark_id' => 1001,'tag_id' => 1002],
        ['bookmark_id' => 1002,'tag_id' => 1000],
        ['bookmark_id' => 1002,'tag_id' => 1001],
    ];

    protected $users = [
        ['id' => 1000,'name' => 'Demo User','email' => 'demo@example.com','password' => '$2y$10$/clqxdb.aWe43VXDUn8tA.yxKbWHZT3rN7gqITFaj32PZHI3.DkzW','dob' => '1999-12-28','created' => '2019-08-10 13:18:12','modified' => '2019-08-10 13:18:12'],
    ];

    protected $tags = [
        ['id' => 1000,'title' => 'Framework','created' => '2019-08-10 13:18:12','modified' => '2019-08-10 13:18:12'],
        ['id' => 1001,'title' => 'PHP','created' => '2019-08-10 13:18:12','modified' => '2019-08-10 13:18:12'],
        ['id' => 1002,'title' => 'Search Engine','created' => '2019-08-10 13:18:12','modified' => '2019-08-10 13:18:12'],
    ];
}
