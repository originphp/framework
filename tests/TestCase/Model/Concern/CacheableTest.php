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

use Origin\Cache\Cache;
use Origin\Model\Model;

use Origin\Model\ModelRegistry;
use Origin\Model\Concern\Cacheable;
use Origin\TestSuite\OriginTestCase;
use Origin\Model\Concern\Timestampable;
use Origin\Model\Exception\NotFoundException;

class YetAnotherArticle extends Model
{
    protected $table = 'articles';

    use Timestampable,Cacheable;

    public function initialize(array $config): void
    {
        $this->hasMany('Comment', [
            'className' => AnotherComment::class,
            'foreignKey' => 'article_id',
            'order' => 'id ASC'
        ]);
       
        $this->cacheConfig('cache-test');
    }
}

class AnotherComment extends Model
{
    protected $table = 'comments';

    use Timestampable,Cacheable;

    public function initialize(array $config): void
    {
        $this->belongsTo('Article', [
            'className' => YetAnotherArticle::class,
            'foreignKey' => 'article_id'
        ]);
        $this->cacheConfig('cache-test');
    }
}

class CacheableTest extends OriginTestCase
{
    protected $fixtures = ['Origin.Article','Origin.Comment'];

    protected function setUp(): void
    {
        Cache::config('cache-test', ['engine' => 'Array']);
        $this->Article = ModelRegistry::get('YetAnotherArticle', ['className' => YetAnotherArticle::class]);
    }

    protected function tearDown(): void
    {
        Cache::clear(['config' => 'cache-test']);
    }

    public function testFind()
    {
        // Load into cache and test its from cache
        $article = $this->Article->get(1000);
        $this->Article->updateColumn($article->id, 'title', 'not cached');
        $cached = $this->Article->get(1000);
        $this->assertEquals('Article #1', $cached->title);
    }

    public function testAggregate()
    {
        $article = $this->Article->find('first');

        $count = $this->Article->count();
        $this->Article->deleteAll(['id' => $article->id]); // avoid cache

        $this->assertEquals($count, $this->Article->count());
    }

    /**
     * @depends testFind
     */
    public function testEnableDisableCache()
    {
        // Load into cache and test its from cache
        $article = $this->Article->get(1000);
        $this->Article->updateColumn($article->id, 'title', 'not cached');
        $cached = $this->Article->get(1000);
        $this->assertEquals('Article #1', $cached->title);

        $this->Article->disableCache();
        $cached = $this->Article->get(1000);
        $this->assertEquals('not cached', $cached->title);

        $this->Article->enableCache();
        $cached = $this->Article->get(1000);
        $this->assertEquals('Article #1', $cached->title);
    }

    public function testClearCache()
    {
        // Load into cache and test its from cache
        $article = $this->Article->get(1000);
        $this->Article->updateColumn($article->id, 'title', 'not cached');
        $cached = $this->Article->get(1000);
        $this->assertEquals('Article #1', $cached->title);

        $this->Article->invalidateCache(false);
        $cached = $this->Article->get(1000);
        $this->assertEquals('not cached', $cached->title);
    }

    /**
     * @depends testFind
     */
    public function testCachedAssociated()
    {
        // Load into cache and test its from cache
        $article = $this->Article->get(1000, ['associated' => ['Comment']]);
       
        // Update a different model that is associated with Article
        $article->comments[0]->description = 'foo';
        $this->Article->Comment->save($article->comments[0]);

        // Load Data through Association
        $clean = $this->Article->get(1000, ['associated' => ['Comment']]);
        $this->assertEquals('foo', $clean->comments[0]->description);

        // Load Data through normal Load
        $comment = $this->Article->Comment->get(1001);
        $this->assertEquals('foo', $comment->description);
    }

    /**
     * @depends testFind
     */
    public function testClearCacheAssociated()
    {
       
        // Load into cache and test its from cache
        $article = $this->Article->get(1000, ['associated' => ['Comment']]);
        $commentId = $article->comments[0]->id;
    
        $this->Article->Comment->updateColumn($commentId, 'description', 'foo bar');

        $cached = $this->Article->get(1000, ['associated' => ['Comment']]);
        $this->assertEquals($article->comments[0]->description, $cached->comments[0]->description);
       
        // Clear cache on associated model then call on original model
        $this->Article->Comment->invalidateCache();
        $article = $this->Article->get(1000, ['associated' => ['Comment']]);
        $this->assertEquals('foo bar', $article->comments[0]->description);
    }

    /**
     * @depends testFind
     */
    public function testSave()
    {
        $article = $this->Article->get(1000);
     
        # Check save clears the cache
        $article->title = 'fooz';
        $this->Article->save($article);
        $article3 = $this->Article->get(1000);
        $this->assertEquals('fooz', $article3->title);
    }

    /**
     * @depends testFind
     */
    public function testDelete()
    {
        $article = $this->Article->get(1000);

        // Check delete record no longer is retrived
        $this->Article->delete($article);
        $this->expectException(NotFoundException::class);
        $this->Article->get(1000);
    }
}
