<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Model\Behavior;

use Origin\Model\Model;
use Origin\TestSuite\OriginTestCase;
use Origin\Model\ModelRegistry;

class Post extends Model
{
    public function initialize(array $config)
    {
        $this->loadBehavior('Timestamp');
        $this->loadBehavior('CounterCache');
        $this->hasMany('Reply');
    }
}

class Reply extends Model
{
    public function initialize(array $config)
    {
        $this->loadBehavior('Timestamp');
        $this->loadBehavior('CounterCache');
        $this->belongsTo('Post', [
            'counterCache' => true
        ]);
    }
}

class CounterCacheBehaviorTest extends OriginTestCase
{
    public $fixtures = ['Origin.CounterCachePost','Origin.CounterCacheReply'];

    public function setUp(): void
    {
        parent::setUp();
        $this->Post = ModelRegistry::get('Post', [
            'className' => 'Origin\Test\Model\Behavior\Post',
            'table' => 'counter_cache_posts',
            'datasource' => 'test'
        ]);
   
        $this->Reply = ModelRegistry::get('Reply', [
            'className' => 'Origin\Test\Model\Behavior\Reply',
            'table' => 'counter_cache_replies',
            'datasource' => 'test'
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
        $this->Post->saveField(1, 'replies_count', 5);

        $reply = $this->Post->Reply->new([
            'post_id' => 1,
            'description' => 'some random text'
        ]);
  
        $this->assertTrue($this->Post->Reply->save($reply));
        $post = $this->Post->get(1);
        $this->assertEquals(6, $post->replies_count);

        $this->Post->Reply->delete($reply);
        $post = $this->Post->get(1);
        $this->assertEquals(5, $post->replies_count);
    }
}
