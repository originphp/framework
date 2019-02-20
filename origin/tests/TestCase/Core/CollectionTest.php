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
namespace Origin\Test\Core;

use Origin\Core\Collection;
use Origin\Model\Entity;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->books = [];
        $book = new Entity(['id'=>1001,'name'=>'The Outsiders: Eight Unconventional CEOs and Their Radically Rational Blueprint for Success','category'=>'Business','in_stock'=>true]);
        $book->author = new Entity(['id'=>600,'name'=>'William N. Thorndike']);
        $this->books[] = $book;

        $book = new Entity(['id'=>1000,'name'=>'How to Win Friends & Influence People','category'=>'Personal Development','in_stock'=>true]);
        $book->author = new Entity(['id'=>500,'name'=>'Dale Carnegie']);
        $this->books[] = $book;

        $book = new Entity(['id'=>1002,'name'=>'The Art of War','category'=>'Military','category'=>'Personal Development','in_stock'=>false]);
        $book->author = new Entity(['id'=>300,'name'=>'Sun Tzu']);
        $this->books[] = $book;

        $book = new Entity(['id'=>1004,'name'=>'Think and Grow Rich!','category'=>'Personal Development','in_stock'=>false]);
        $book->author = new Entity(['id'=>200,'name'=>'Napoleon Hill']);
        $this->books[] = $book;

        $book = new Entity(['id'=>1003,'name'=>'The 7 Habits of Highly Effective People','category'=>'Personal Development','in_stock'=>true]);
        $book->author = new Entity(['id'=>250,'name'=>'Stephen R. Covey']);
        $this->books[] = $book;

        // Convert entities to array
        $this->array = [];
        foreach ($this->books as $book) {
            $this->array[] = $book->toArray();
        }
    }

    public function testEach()
    {
        $data = ['tom','mary','jane'];
        $collection = collection($data);
       
        $collection->each(function ($value, $key) {
            global $testEachArray;
            $testEachArray[$key] = $value;
        });
        global $testEachArray;
        $this->assertEquals($data, $testEachArray);
    }
    public function testExtract()
    {
        $titles = ['The Outsiders: Eight Unconventional CEOs and Their Radically Rational Blueprint for Success','How to Win Friends & Influence People','The Art of War','Think and Grow Rich!','The 7 Habits of Highly Effective People'];
        $authors = ['William N. Thorndike','Dale Carnegie','Sun Tzu','Napoleon Hill','Stephen R. Covey'];
        // test nested array with objects
        $collection = collection($this->books);
        $this->assertEquals($titles, $collection->extract('name')->toArray());
        $this->assertEquals($authors, $collection->extract('author.name')->toArray());

        // test nested arrays
        $collection = collection($this->array);
        $this->assertEquals($titles, $collection->extract('name')->toArray());
        $this->assertEquals($authors, $collection->extract('author.name')->toArray());
    }
    public function testMap()
    {
        $collection = collection(['a'=>1,'b'=>2,'c'=>3]);
      
        $new = $collection->map(function ($value, $key) {
            return $value + 1;
        });
        $this->assertEquals(['a'=>2,'b'=>3,'c'=>4], $new->toArray());
    }
    public function testCombine()
    {
        $expected =  [
            'The Outsiders: Eight Unconventional CEOs and Their Radically Rational Blueprint for Success' => 'William N. Thorndike',
            'How to Win Friends & Influence People' => 'Dale Carnegie',
            'The Art of War' => 'Sun Tzu',
            'Think and Grow Rich!' => 'Napoleon Hill',
            'The 7 Habits of Highly Effective People' => 'Stephen R. Covey'];
        
        $collection = collection($this->books);
        $this->assertEquals($expected, $collection->combine('name', 'author.name')->toArray());

        $collection = collection($this->array);
        $this->assertEquals($expected, $collection->combine('name', 'author.name')->toArray());

        $array = [
            ['id'=>1,'name'=>'Tom','team'=>'Manchester United'],
            ['id'=>2,'name'=>'Dave','team'=>'Manchester United'],
            ['id'=>3,'name'=>'James','team'=>'West Ham'],
        ];
        $collection = collection($array);
        $expected =[
            'Manchester United'=>[
                [1=>'Tom'],
                [2=>'Dave'],
            ],
            'West Ham'=>[
                [3=>'James']
            ]
        ];
        $this->assertEquals($expected, $collection->combine('id', 'name', 'team')->toArray());
    }

    public function testChunk()
    {
        $collection = collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]);
        $chunks = $collection->chunk(5)->toList();
        $this->assertEquals(3, count($chunks));
        $this->assertEquals(5, count($chunks[0]));
        $this->assertEquals(5, count($chunks[1]));
        $this->assertEquals(2, count($chunks[2]));
    }

    public function testFilter()
    {
        $collection = collection($this->books);
        $inStock = $collection->filter(function ($book) {
            return $book->in_stock ===  true;
        });
        $books = $inStock->toArray();
        $this->assertEquals(3, count($books));

        $collection = collection($this->array);
        $outOfStock = $collection->filter(function ($book) {
            return $book['in_stock'] ===  false;
        });
        $books = $outOfStock->toArray();
        $this->assertEquals(2, count($books));
    }

    public function testReject()
    {
        $collection = collection($this->books);
        $outOfStock = $collection->reject(function ($book) {
            return $book->in_stock === true;
        });
    
        $books = $outOfStock->toArray();
        $this->assertEquals(2, count($books));
    }
    public function testEvery()
    {
        $collection = collection($this->books);
     
        $this->assertFalse($collection->every(function ($book) {
            return $book->in_stock === true;
        }));
        
        // Filter results to only those in stock
        $inStock = $collection->filter(function ($book) {
            return $book->in_stock ===  true;
        });
       
        $this->assertTrue($inStock->every(function ($book) {
            return $book->in_stock === true;
        }));
    }

    public function testSome()
    {
        $collection = collection($this->books);
     
        $this->assertTrue($collection->some(function ($book) {
            return $book->in_stock === true;
        }));
        
        $outOfStock = $collection->reject(function ($book) {
            return $book->in_stock === true;
        });

        $this->assertFalse($outOfStock->some(function ($book) {
            return $book->in_stock === true;
        }));
    }

    public function testSortBy()
    {
        $collection = collection($this->books);
        $sorted = $collection->sortBy('name', SORT_ASC, SORT_STRING)->extract('name')->toList();
        $expected = [
            "How to Win Friends & Influence People",
            "The 7 Habits of Highly Effective People",
            "The Art of War",
            "The Outsiders: Eight Unconventional CEOs and Their Radically Rational Blueprint for Success",
            "Think and Grow Rich!"
        ];
        $this->assertEquals($expected, $sorted);
        $sorted = $collection->sortBy('name', SORT_DESC, SORT_STRING)->extract('name')->toList();
        $expected = [ "Think and Grow Rich!",
        "The Outsiders: Eight Unconventional CEOs and Their Radically Rational Blueprint for Success",
        "The Art of War",
        "The 7 Habits of Highly Effective People",
        "How to Win Friends & Influence People"];
        $this->assertEquals($expected, $sorted);

        $sorted = $collection->sortBy(function ($book) {
            return $book->author->name . '-' . $book->name;
        }, SORT_ASC, SORT_STRING);
        $sorted->map(function ($book) {
            $book->index = $book->author->name . '-' . $book->name;
            return $book;
        });
        $sorted = $sorted->extract('index')->toList();
        $expected = [
            "Dale Carnegie-How to Win Friends & Influence People",
            "Napoleon Hill-Think and Grow Rich!",
            "Stephen R. Covey-The 7 Habits of Highly Effective People",
            "Sun Tzu-The Art of War",
            "William N. Thorndike-The Outsiders: Eight Unconventional CEOs and Their Radically Rational Blueprint for Success"
            ];
        $this->assertEquals($expected, $sorted);
        //pr(json_encode($sorted, JSON_PRETTY_PRINT));
    }

    /**
     * @depends testSortBy
     */
    public function testMin()
    {
        $collection = collection($this->books);
        $book = $collection->min('id');
        $this->assertEquals('How to Win Friends & Influence People', $book->name);

        $book = $collection->min('author.id');
        $this->assertEquals('Napoleon Hill', $book->author->name);

        $book = $collection->min(function ($book) {
            return $book->author->id;
        });
        $this->assertEquals('Napoleon Hill', $book->author->name);
    }

    /**
     * @depends testSortBy
     */
    public function testMax()
    {
        $collection = collection($this->books);
        $book = $collection->max('id');
        $this->assertEquals('Think and Grow Rich!', $book->name);

        $book = $collection->max('author.id');
        $this->assertEquals('William N. Thorndike', $book->author->name);

        $book = $collection->max(function ($book) {
            return $book->author->id;
        });
        $this->assertEquals('William N. Thorndike', $book->author->name);
    }

    public function testSumOf()
    {
        $collection = collection($this->books);
        $this->assertEquals(5010, $collection->sumOf('id'));
        $this->assertEquals(5510, $collection->sumOf(function ($book) {
            return $book->id + 100;
        }));
        $this->assertEquals(1850, $collection->sumOf('author.id'));
    }

    public function testAvg()
    {
        $collection = collection($this->books);
        $this->assertEquals(1002, $collection->avg('id'));
        $this->assertEquals(1102, $collection->avg(function ($book) {
            return $book->id + 100;
        }));
        $this->assertEquals(370, $collection->avg('author.id'));
    }

    public function testMedian()
    {
        $collection = collection($this->books);
        $this->assertEquals(1002, $collection->median('id'));
        $this->assertEquals(1102, $collection->median(function ($book) {
            return $book->id + 100;
        }));
        $this->assertEquals(300, $collection->median('author.id'));
    }

    public function testCountBy()
    {
        /*$classResults = $students->countBy(function ($student) {
    return $student->grade > 6 ? 'approved' : 'denied';
});*/
        $collection = collection($this->books);
        $expected = ['Business' => 1,'Personal Development'=>4];
        $this->assertEquals($expected, $collection->countBy('category'));

        $expected = ['odd'=>2,'even'=>3];
        $this->assertEquals($expected, $collection->countBy(function ($book) {
            return $book->id % 2 == 0 ? 'even' : 'odd';
        }));
    }

    public function testGroupBy()
    {
        $collection = collection($this->books);
        $result = $collection->groupBy('category')->toArray();
        $this->assertEquals(1001, $result['Business'][0]->id);
        $this->assertEquals(1000, $result['Personal Development'][0]->id);
        $this->assertEquals(1002, $result['Personal Development'][1]->id);

        $result = $collection->groupBy(function ($book) {
            return $book->id % 2 == 0 ? 'even' : 'odd';
        })->toArray();
        $this->assertEquals(2, count($result['odd']));
        $this->assertEquals(3, count($result['even']));
    }
    public function testInsert()
    {
        $users = [
            ['name'=>'James'],
            ['name'=>'Roise'],
            ['name'=>'Liz']
        ];
        $collection = collection($users);
        $result = $collection->insert('status', 'active')->toArray();
        $this->assertEquals(['name'=>'James','status'=>'active'], $result[0]);
        $result = $collection->insert('profile.status', 'active')->toArray();
        $this->assertEquals(['name'=>'James','profile'=>['status'=>'active']], $result[0]);
      
        $collection = collection($this->books);
        $result = $collection->insert('location', 'head office')->toArray();
        $this->assertEquals('head office', $result[0]->location);
    }
    public function testTake()
    {
        $collection = collection($this->books);
        $first = $collection->take(5);
        pr($first->toArray());
        $second = $collection->take(2);
        pr($second->toArray());
    }
}
