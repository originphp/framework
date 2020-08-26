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

namespace Origin\Test\Model;

use Origin\Model\Model;
use Origin\Model\ModelRegistry;
use Origin\TestSuite\OriginTestCase;

/**
 * Some of these tests are covered in ModelTest but I prefered to do this
 * from scratch in a cleaner way.
 */
class FinderTest extends OriginTestCase
{
    protected $fixtures = [
        'Framework.Article',
        'Framework.ArticlesTag',
        'Framework.Author',
        'Framework.Book',
        'Framework.Comment',
        'Framework.Tag',
        'Framework.Address',
        'Framework.Thread', // Use this to test increment/decrement
    ];
    /**
     * Model
     *
     * @var \Origin\Model\Model;
     */
    protected $Article = null;
    public function setUp(): void
    {
        $this->Article = new Model([
            'name' => 'Article',
            'connection' => 'test',
        ]);
        $this->Author = new Model([
            'name' => 'Author',
            'connection' => 'test',
        ]);
        $this->Book = new Model([
            'name' => 'Book',
            'connection' => 'test',
        ]);
        $this->Comment = new Model([
            'name' => 'Comment',
            'connection' => 'test',
        ]);

        $this->Tag = new Model([
            'name' => 'Tag',
            'connection' => 'test',
        ]);

        $this->Address = new Model([
            'name' => 'Address',
            'connection' => 'test',
        ]);

        ModelRegistry::set('Article', $this->Article);
        ModelRegistry::set('Author', $this->Author);
        ModelRegistry::set('Book', $this->Book);
        ModelRegistry::set('Comment', $this->Comment);
        ModelRegistry::set('Tag', $this->Tag);
        ModelRegistry::set('Address', $this->Address);
    }

    public function testFindBelongsTo()
    {
        $this->Article->belongsTo('Author');

        $article = $this->Article->get(1000, [
            'associated' => 'Author'
        ]);

        $this->assertEquals(1001, $article->author->id);
        $this->assertNotEmpty($article->author->location);
        $this->assertNotEmpty($article->author->created);
        $this->assertNotEmpty($article->author->modified);
    }

    /**
     * @depends testFindBelongsTo
     */
    public function testFindBelongsToDefaultConditions()
    {
        $this->Article->belongsTo('Author', [
            'conditions' => ['id' => 1234]
        ]);

        $article = $this->Article->get(1000, [
            'associated' => 'Author'
        ]);

        $this->assertNull($article->author);
    }

    /**
    * @depends testFindBelongsToDefaultConditions
    */
    public function testFindBelongsToAssociatedConditions()
    {
        $this->Article->belongsTo('Author', [
            'conditions' => ['id' => 1234]
        ]);

        $article = $this->Article->get(1000, [
            'associated' => 'Author'
        ]);

        $this->assertNull($article->author);

        $article = $this->Article->get(1000, [
            'associated' => [
                'Author' => [
                    'conditions' => ['id' => 1001]
                ]
            ]
        ]);

        $this->assertEquals(1001, $article->author->id);
    }

    public function testFindBelongsToDefaultFields()
    {
        $this->Article->belongsTo('Author', [
            'fields' => ['id','name','description','location']
        ]);

        $article = $this->Article->get(1000, [
            'associated' => 'Author'
        ]);

        $this->assertEquals(1001, $article->author->id);
        $this->assertNotEmpty($article->author->location);
        $this->assertNull($article->author->created);
        $this->assertNull($article->author->modified);
    }

    public function testFindBelongsToAssociatedOveride()
    {
        $this->Article->belongsTo('Author', [
            'fields' => ['id','name','description','location']
        ]);

        $article = $this->Article->get(1000, [
            'associated' => [
                'Author' => [
                    'fields' => ['id','name','description','created']
                ]
            ]
        ]);

        $this->assertEquals(1001, $article->author->id);
        $this->assertNull($article->author->location);
        $this->assertNotEmpty($article->author->created);
        $this->assertNull($article->author->modified);
    }

    public function testFindHasOne()
    {
        $this->Author->hasOne('Address');

        $author = $this->Author->get(1000, [
            'associated' => 'Address'
        ]);

        $this->assertEquals(1002, $author->address->id);
        $this->assertNotEmpty($author->address->description);
        $this->assertNotEmpty($author->address->created);
        $this->assertNotEmpty($author->address->modified);
    }

    /**
     * @depends testFindHasOne
     */
    public function testFindHasOneDefaultConditions()
    {
        $this->Author->hasOne('Address', [
            'conditions' => ['id' => 1234]
        ]);

        $author = $this->Author->get(1000, [
            'associated' => 'Address'
        ]);

        $this->assertNull($author->address);
    }

    /**
    * @depends testFindHasOneDefaultConditions
    */
    public function testFindHasOneAssociatedConditions()
    {
        $this->Author->hasOne('Address', [
            'conditions' => ['id' => 1234]
        ]);

        $author = $this->Author->get(1000, [
            'associated' => 'Address'
        ]);

        $this->assertNull($author->address);

        $author = $this->Author->get(1000, [
            'associated' => [
                'Address' => ['conditions' => ['id' => 1002]]
            ]
        ]);

        $this->assertEquals(1002, $author->address->id);
    }

    public function testFindHasOneDefaultFields()
    {
        $this->Author->hasOne('Address', [
            'fields' => ['id','author_id','description']
        ]);

        $author = $this->Author->get(1000, [
            'associated' => 'Address'
        ]);

        $this->assertEquals(1002, $author->address->id);
        $this->assertNotEmpty($author->address->description);
        $this->assertNull($author->address->created);
        $this->assertNull($author->address->modified);
    }

    public function testFindHasOneDefaultFieldsOveride()
    {
        $this->Author->hasOne('Address', [
            'fields' => ['id','author_id','description']
        ]);

        $author = $this->Author->get(1000, [
            'associated' => [
                'Address' => ['fields' => ['id','author_id','description','created']]
            ]
        ]);

        $this->assertEquals(1002, $author->address->id);
        $this->assertNotEmpty($author->address->description);
        $this->assertNotEmpty($author->address->created);
        $this->assertNull($author->address->modified);
    }

    public function testFindHasMany()
    {
        $this->Article->hasMany('Comment');

        $article = $this->Article->get(1000, [
            'associated' => 'Comment'
        ]);

        $this->assertCount(2, $article->comments);
        $this->assertEquals(1001, $article->comments[0]->id);
        $this->assertNotEmpty($article->comments[0]->description);
        $this->assertNotEmpty($article->comments[0]->created);
        $this->assertNotEmpty($article->comments[0]->modified);
    }

    public function testFindHasManyDefaultOrder()
    {
        $this->Article->hasMany('Comment', [
            'order' => ['id DESC']
        ]);

        $article = $this->Article->get(1000, [
            'associated' => 'Comment'
        ]);

        $this->assertCount(2, $article->comments);
        $this->assertEquals(1002, $article->comments[0]->id);
    }

    /**
     * @depends testFindHasManyDefaultOrder
     */
    public function testFindHasManyAssociatedOrder()
    {
        $this->Article->hasMany('Comment', [
            'order' => ['id DESC']
        ]);

        $article = $this->Article->get(1000, [
            'associated' => [
                'Comment' => [
                    'order' => 'id ASC'
                ]
            ]
        ]);

        $this->assertCount(2, $article->comments);
        $this->assertEquals(1001, $article->comments[0]->id);
    }

    /**
     * @depends testFindHasMany
     */
    public function testFindHasManyDefaultConditions()
    {
        $this->Article->hasMany('Comment', [
            'conditions' => ['id' => 1234]
        ]);

        $article = $this->Article->get(1000, [
            'associated' => 'Comment'
        ]);

        $this->assertNull($article->comment);
    }

    /**
     * @depends testFindHasMany
     */
    public function testFindHasManyAssociatedConditions()
    {
        $this->Article->hasMany('Comment', [
            'conditions' => ['id' => 1234]
        ]);

        $article = $this->Article->get(1000, [
            'associated' => 'Comment'
        ]);

        $this->assertNull($article->comment);

        $article = $this->Article->get(1000, [
            'associated' => [
                'Comment' => ['conditions' => ['id' => 1001]]
            ]
        ]);

        $this->assertEquals(1001, $article->comments[0]->id);
    }

    public function testFindHasManyDefaultFields()
    {
        $this->Article->hasMany('Comment', [
            'fields' => ['id','article_id','description']
        ]);

        $article = $this->Article->get(1000, [
            'associated' => 'Comment'
        ]);

        $this->assertCount(2, $article->comments);
        $this->assertEquals(1001, $article->comments[0]->id);
        $this->assertNotEmpty($article->comments[0]->description);
        $this->assertNull($article->comments[0]->created);
        $this->assertNull($article->comments[0]->modified);
    }

    public function testFindHasManyAssociatedOveride()
    {
        $this->Article->hasMany('Comment', [
            'fields' => ['id','article_id','description']
        ]);

        $article = $this->Article->get(1000, [
            'associated' => [
                'Comment' => ['fields' => ['id','article_id','description','created']]
            ]
        ]);

        $this->assertCount(2, $article->comments);
        $this->assertEquals(1001, $article->comments[0]->id);
        $this->assertNotEmpty($article->comments[0]->description);
        $this->assertNotEmpty($article->comments[0]->created);
        $this->assertNull($article->comments[0]->modified);
    }

    public function testFindHasAndBelongsToMany()
    {
        $this->Article->hasAndBelongsToMany('Tag');

        $article = $this->Article->get(1000, [
            'associated' => 'Tag'
        ]);

        $this->assertCount(2, $article->tags);
        $this->assertEquals(1001, $article->tags[0]->id);
        $this->assertNotEmpty($article->tags[0]->title);
        $this->assertNotEmpty($article->tags[0]->created);
        $this->assertNotEmpty($article->tags[0]->modified);
    }

    public function testFindHasAndBelongsToManyDefaultOrder()
    {
        $this->Article->hasAndBelongsToMany('Tag', [
            'order' => 'id DESC'
        ]);

        $article = $this->Article->get(1000, [
            'associated' => 'Tag'
        ]);

        $this->assertCount(2, $article->tags);
        $this->assertEquals(1002, $article->tags[0]->id);
    }

    /**
     * @depends testFindHasAndBelongsToManyDefaultOrder
     */
    public function testFindHasAndBelongsToManyAssocaitedOrder()
    {
        $this->Article->hasAndBelongsToMany('Tag', [
            'order' => 'id DESC'
        ]);

        $article = $this->Article->get(1000, [
            'associated' => ['Tag' => ['order' => 'id ASC']]
        ]);

        $this->assertCount(2, $article->tags);
        $this->assertEquals(1001, $article->tags[0]->id);
    }

    /**
     * @depends testFindHasAndBelongsToMany
     */
    public function testFindHasAndBelongsToManyDefaultConditions()
    {
        $this->Article->hasAndBelongsToMany('Tag', [
            'conditions' => [
                'article_id' => 1234
            ]
        ]);

        $article = $this->Article->get(1000, [
            'associated' => 'Tag'
        ]);

        $this->assertEmpty($article->tags);
    }

    /**
    * @depends testFindHasAndBelongsToManyDefaultConditions
    */
    public function testFindHasAndBelongsToManyAssociatedConditions()
    {
        $this->Article->hasAndBelongsToMany('Tag', [
            'conditions' => [
                'article_id' => 1234
            ]
        ]);

        $article = $this->Article->get(1000, [
            'associated' => 'Tag'
        ]);

        $this->assertEmpty($article->tags);

        $article = $this->Article->get(1000, [
            'associated' => [
                'Tag' => ['conditions' => ['article_id' => 1000]],
            ]
        ]);
  
        $this->assertNotEmpty($article->tags);
        $this->assertEquals(1001, $article->tags[0]->id);
    }

    public function testFindHasAndBelongsToManyDefaultFields()
    {
        $this->Article->hasAndBelongsToMany('Tag', [
            'fields' => ['id','title']
        ]);

        $article = $this->Article->get(1000, [
            'associated' => 'Tag'
        ]);

        $this->assertCount(2, $article->tags);
        $this->assertEquals(1001, $article->tags[0]->id);
        $this->assertNotEmpty($article->tags[0]->title);
        $this->assertNull($article->tags[0]->created);
        $this->assertNull($article->tags[0]->modified);
    }

    public function testFindHasAndBelongsToManyAssociatedOveride()
    {
        $this->Article->hasAndBelongsToMany('Tag', [
            'fields' => ['id','title']
        ]);

        $article = $this->Article->get(1000, [
            'associated' => [
                'Tag' => ['fields' => ['id','title','created']]
            ]
        ]);

        $this->assertCount(2, $article->tags);
        $this->assertEquals(1001, $article->tags[0]->id);
        $this->assertNotEmpty($article->tags[0]->title);
        $this->assertNotEmpty($article->tags[0]->created);
        $this->assertNull($article->tags[0]->modified);
    }
}
