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

namespace Origin\Test\Model\Concern;

use Origin\Model\Model;
use Origin\Model\ModelRegistry;
use Origin\TestSuite\OriginTestCase;
use Origin\Model\Concern\Timestampable;

/**
 * Counter cache should load Automatically
 */
class Post extends Model
{
    use Timestampable;
    public function initialize(array $config): void
    {
        $this->hasMany('Reply');
    }
}

class Reply extends Model
{
    use Timestampable;
    public function initialize(array $config): void
    {
        $this->belongsTo('Post', [
            'counterCache' => true,
        ]);
    }
}

class CounterCacheableTest extends OriginTestCase
{
    protected $fixtures = ['Origin.CounterCachePost','Origin.CounterCacheReply'];

    protected function setUp(): void
    {
        $this->Post = ModelRegistry::get('Post', [
            'className' => 'Origin\Test\Model\Concern\Post',
            'table' => 'counter_cache_posts',
            'connection' => 'test',
        ]);
   
        $this->Reply = ModelRegistry::get('Reply', [
            'className' => 'Origin\Test\Model\Concern\Reply',
            'table' => 'counter_cache_replies',
            'connection' => 'test',
        ]);
    }

    public function testOnSave()
    {
        $reply = $this->Post->Reply->new();
        $reply->post_id = 1;
        $reply->description = 'some random text';
        $this->assertTrue($this->Post->Reply->save($reply));
        $post = $this->Post->get(1);
        $this->assertEquals(1, $post->replies_count);
    }

    public function testOnDelete()
    {
        $this->Post->updateColumn(1, 'replies_count', 5);

        $reply = $this->Post->Reply->new([
            'post_id' => 1,
            'description' => 'some random text',
        ]);
  
        $this->assertTrue($this->Post->Reply->save($reply));
        $post = $this->Post->get(1);
        $this->assertEquals(6, $post->replies_count);

        $this->Post->Reply->delete($reply);
        $post = $this->Post->get(1);
        $this->assertEquals(5, $post->replies_count);
    }
}
