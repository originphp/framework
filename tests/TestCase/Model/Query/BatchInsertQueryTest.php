<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Query;

use Origin\Model\Model;
use Origin\Model\Query\BatchInsertQuery;
use Origin\Model\Query\QueryObject;
use Origin\TestSuite\OriginTestCase;

class BatchInsertQueryTest extends OriginTestCase
{
    protected $fixtures = [
        'Framework.Post'
    ];
    protected function setUp()  : void
    {
        $this->Post = new Model([
            'name' => 'Post',
            'connection' => 'test',
        ]);
    }
    public function testInsert()
    {
        $records = [];
        for ($i=0;$i<1000;$i++) {
            $records[] = [
                'title' => uniqid(),
                'body' => bin2hex(random_bytes(mt_rand(10, 200))),
                'published' => rand(0, 1),
                'created' => now(),
                'modified' => now()
            ];
        }

        $this->assertTrue((new BatchInsertQuery($this->Post))->execute($records));
        $this->assertEquals(1003, $this->Post->find('count'));
    }
}
