<?php
declare(strict_types = 1);
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
namespace Origin\Utility;

use Iterator;
use Countable;

class Collection implements Iterator, Countable
{
    /**
     * Holds the Items
     *
     * @var array|\Origin\Model\Collection
     */
    protected $items = null;

    protected $position = 0;

    public function __construct($items)
    {
        $this->items = $items;
    }

    /**
     * Magic method is trigged when calling var_dump
     *
     * @return array
     */
    public function __debugInfo()
    {
        return $this->toArray();
    }

    /**
     * Extracts a single column for a collection
     *
     * $collection = new Collection($books);
     *
     * $authors = $collection->extract('author.name');
     *
     * $booklist = $collection->extract(function ($book) {
     *       return $book->name . ' written by ' . $book->author->name;
     *    });
     *
     * @param string|callable $callback
     * @return \Origin\Utility\Collection
     */
    public function extract($callback) : Collection
    {
        $callback = $this->initializeCallback($callback);
        $result = [];
        foreach ($this->items as $key => $value) {
            $result[$key] = $callback($value);
        }

        return new Collection($result);
    }

    /**
     * Go through each item of the collection, this does not modify data, use map for that. Return
     * false to exit the loop
     *
     * $collection->each(function ($value, $key) {
     *      echo "{$key}:{$value}";
     *  });
     *
     * @param callable $callback
     * @return \Origin\Utility\Collection
     */
    public function each(callable $callback) : Collection
    {
        foreach ($this->items as $key => $value) {
            $result = $callback($value, $key);
            if ($result === false) {
                break;
            }
        }

        return $this;
    }

    /**
     *  This will iterate through each item in the collection and pass value through a callback
     *  which can modify the data and return it, creating a new collection in the process.
     *
     *   $collection = new Collection(['a'=>1,'b'=>2,'c'=>3]);
     *
     *   // using a callable must return a value
     *   $collection->map(function ($value, $key) {
     *      return $value + 1;
     *   });
     * @param callable $callback
     * @return \Origin\Utility\Collection
     */
    public function map(callable $callback) : Collection
    {
        $items = [];
        foreach ($this->items as $key => $value) {
            $items[$key] = $callback($value, $key);
        }

        return new Collection($items);
    }

    /**
    * Creates a new collection using keys and values in an existing collection
    *
    *  // [1=>'Tom','2'=>'James']
    *  $result => $collection->combine('id', 'name')
    *
    *  You can also group the results
    *  // ['admin'=>[1=>'tom']]
    *  $result => $collection->combine('id', 'name','profile');
    *
    * @param string $keyPath
    * @param string $valuePath
    * @return \Origin\Utility\Collection
    */
    public function combine(string $keyPath, string $valuePath, string $groupPath = null) : Collection
    {
        $options = [
            'keyPath' => $keyPath,
            'valuePath' => $valuePath,
            'groupPath' => $groupPath,
        ];

        $callback = function ($data) use ($options) {
            $result = [
                'key' => $this->extractProperty($data, $options['keyPath']),
                'value' => $this->extractProperty($data, $options['valuePath']),
                'group' => null,
            ];

            if ($options['groupPath']) {
                $result['group'] = $this->extractProperty($data, $options['groupPath']);
            }

            return $result;
        };

        $results = [];
        foreach ($this->items as $value) {
            $result = $callback($value);
            if ($result['group']) {
                if (! isset($results[$result['group']])) {
                    $results[$result['group']] = [];
                }
                $results[$result['group']][] = [$result['key'] => $result['value']];
            } else {
                $results[$result['key']] = $result['value'];
            }
        }

        return new Collection($results);
    }

    /**
     * Chunks a collection into multiple parts
     *
     *  $collection = collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]);
     *  // [[1,2,3,4,5],[6,7,8,9,10],[11,12]]
     *  $chunks = $collection->chunk(5)->toList();
     *
     * @param integer $chunkSize
     * @return \Origin\Utility\Collection
     */
    public function chunk(int $chunkSize) : Collection
    {
        $chunks = [];
        $counter = 0;
        foreach ($this->items as $key => $value) {
            if (! isset($chunks[$counter])) {
                $chunks[$counter] = [];
            }
            $chunks[$counter][$key] = $value;
            
            if ($chunkSize == count($chunks[$counter])) {
                $counter++;
            }
        }

        return new Collection($chunks);
    }

    /**
     * Filters results using a callback function
     *
     * $inStock = $collection->filter(function ($book) {
     *       return $book->in_stock ===  true;
     *   });
     *
     * @param callable $callback
     * @return \Origin\Utility\Collection
     */
    public function filter(callable $callback) : Collection
    {
        $results = [];
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                $results[$key] = $value;
            }
        }

        return new Collection($results);
    }

    /**
     * This is the inverse of filter
     *
     * $notInStock = $collection->reject(function ($book) {
     *       return $book->in_stock ===  true;
     *   });
     *
     * @param callable $callback
     * @return \Origin\Utility\Collection
     */
    public function reject(callable $callback) : Collection
    {
        $results = [];
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key) === false) {
                $results[$key] = $value;
            }
        }

        return new Collection($results);
    }

    /**
     * Run truth tests on every item in the collection
     *
     * $collection->every(function ($book) {
     *    return $book->in_stock > 0;
     * });
     *
     * @param  callable $callback
     * @return bool $result

     */
    public function every(callable $callback) : bool
    {
        foreach ($this->items as $key => $value) {
            if (! $callback($value, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check to see if at least one item matches the filter
     *
     * $collection->some(function ($book) {
     *    return $book->in_stock > 0;
     * });
     *
     * @param  callable $callback
     * @return bool $result

     */
    public function some(callable $callback) : bool
    {
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sorts by a field or call back
     *
     * $collection->sortBy('author.name');
     *
     * $collection->sortBy(function ($book) {
     *       return $book->author->name . '-' . $book->name;
     *   });
     *
     * @param string|callable $callback
     * @return \Origin\Utility\Collection
     */
    public function sortBy($callback, $direction = SORT_DESC, $type = SORT_NUMERIC) : Collection
    {
        $callback = $this->initializeCallback($callback);

        $results = [];
        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value);
        }
        if ($direction === SORT_DESC) {
            arsort($results, $type);
        } else {
            asort($results, $type);
        }

        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }

        return new Collection($results);
    }

    /**
    * Gets the first item with the smallest value
    *
    * $collection->min('author.score');
    * $collection->min('id');
    *
    * $collection->min(function ($book) {
    *       return $book->author->score;
    *   });
    *
    * @param string|callable $callback
    * @return mixed
    */
    public function min($callback)
    {
        return $this->sortBy($callback, SORT_ASC)->first();
    }

    /**
    * Gets the last item with the largest value
    *
    * $collection->max('author.score');
    * $collection->max('id');
    *
    * $collection->max(function ($book) {
    *       return $book->author->score;
    *   });
    *
    * @param string|callable $callback
    * @return mixed
    */
    public function max($callback)
    {
        return $this->sortBy($callback, SORT_ASC)->last();
    }

    /**
    * Gets the total of a field or return callback
    *
    * $collection->sumOf('author.score');
    * $collection->sumOf('id');
    *
    * $collection->sumOf(function ($book) {
    *       return $book->author->score;
    *   });
    *
    * @param string|callable $callback
    * @return integer
    */
    public function sumOf($callback) : int
    {
        $callback = $this->initializeCallback($callback);
        $sum = 0;
        foreach ($this->items as $key => $value) {
            $sum += $callback($value, $key);
        }

        return $sum;
    }

    /**
    * Gets the average of a field or return callback
    *
    * $collection->avg('author.score');
    * $collection->avg('id');
    *
    * $collection->avg(function ($book) {
    *       return $book->author->score;
    *   });
    *
    * @param string|callable $callback
    * @return integer
    */
    public function avg($callback) : int
    {
        $values = $this->extract($callback)->toArray();

        return array_sum($values) / count($values);
    }

    /**
    * Gets the median of a field or return callback
    *
    * $collection->median('author.score');
    * $collection->median('id');
    *
    * $collection->median(function ($book) {
    *       return $book->author->score;
    *   });
    *
    * @param string|callable $callback
    * @return integer
    */
    public function median($callback) : int
    {
        $values = $this->extract($callback)->toArray();
        $count = count($values);
        sort($values);
        $middle = (int) ($count / 2);

        if ($count % 2) {
            return $values[$middle];
        }

        return ($values[$middle - 1] + $values[$middle]) / 2;
    }

    /**
     * Counts by a field and groups the results
     *
     * $collection->countBy('author.status');
     *
     * // ['odd'=>2,'even'=>3]
     *  $collection->countBy(function ($book) {
     *      return $book->id % 2 == 0 ? 'even' : 'odd';
     *   })
     *
     * @param string|callable $callback
     * @return array
     */
    public function countBy($callback) : array
    {
        $callback = $this->initializeCallback($callback);
        $results = [];
        foreach ($this->items as $key => $value) {
            $result = $callback($value, $key);
            if (! isset($results[$result])) {
                $results[$result] = 0;
            }
            $results[$result] = $results[$result] + 1;
        }

        return $results;
    }

    /**
    * Groups the collection results
    *
    * $collection->groupBy('category');
    * $collection->groupBy('user.status');
    *
    * // This will group data by even and odd id numbers
    *   $collection->groupBy(function ($book) {
    *      return $book->id % 2 == 0 ? 'even' : 'odd';
    *   })
    *
    * @param string|callable $callback
    * @return \Origin\Utility\Collection
    */
    public function groupBy($callback) : Collection
    {
        $callback = $this->initializeCallback($callback);
        $group = [];
        foreach ($this->items as $value) {
            $group[$callback($value)][] = $value;
        }

        return new Collection($group);
    }

    /**
     * Inserts values into a path
     *
     *  $collection->insert('user.emailAccount.active',true);
     *
     * @param string $path key, dotted notation
     * @param mixed $values
     * @return \Origin\Utility\Collection
     */
    public function insert(string $path, $values) : Collection
    {
        $items = [];
        $paths = explode('.', $path);

        foreach ($this->items as $row) {
            $item = &$row;
            foreach ($paths as $key) {
                if (is_object($item)) {
                    if (! isset($item->$key)) {
                        $item->$key = [];
                    }
                    $item = &$item->$key;
                } else {
                    $item = &$item[$key];
                }
            }
            $item = $values;
            $items[] = $row;
        }
       
        return new Collection($items);
    }

    /**
     * Takes a number of items from the collection.
     *
     * @param integer $count how many items to take
     * @param integer $from from which offset
     * @return \Origin\Utility\Collection
     */
    public function take(int $count, int $from = 0) : Collection
    {
        $items = [];
        $to = $from + $count - 1;

        $i = 0;
        foreach ($this->items as $item) {
            if ($i > $to) {
                break;
            }
            if ($i >= $from) {
                $items[] = $item;
            }
            $i++;
        }

        return new Collection($items);
    }

    /**
     * Gets the first item in the collection
     *
     * @return array|object
     */
    public function first()
    {
        return reset($this->items);
    }
    /**
        * Gets the last item in the collection
        *
        * @return array|object
        */
    public function last()
    {
        return end($this->items);
    }

    /**
    * Counts the number of items in the collection
    *
    * @return int
    */
    public function count()
    {
        return count($this->items);
    }

    protected function extractProperty($data, string $path)
    {
        return $this->getColumn($data, explode('.', $path));
    }

    /**
     * Returns an array of the collection items
     *
     * @return array
     */
    public function toArray() : array
    {
        if (is_object($this->items)) {
            return $this->items->toArray();
        }

        return $this->items;
    }

    /**
     * Returns a list made from the array of the collection items (keys removed)
     *
     * @return array
     */
    public function toList() : array
    {
        return array_values($this->toArray());
    }

    /**
    * Returns a callable from callback
    */
    private function initializeCallback($callback)
    {
        if (! is_string($callback)) {
            return $callback;
        }

        $path = explode('.', $callback);

        return function ($data) use ($path) {
            return $this->getColumn($data, $path);
        };
    }

    /**
    * Extracts a value of column from data

    * @param array|object $data
    * @param array $path explode('.','Student.name')
    * @return mixed $result
    */
    private function getColumn($data, array $path)
    {
        $value = null;
        foreach ($path as $key) {
            if (is_array($data)) {
                if (! isset($data[$key])) {
                    return null;
                }
                $value = $data[$key];
            } elseif (is_object($data)) {
                if (! isset($data->$key)) {
                    return null;
                }
                $value = $data->$key;
            }
            $data = $value; // Next In path
        }

        return $value;
    }
 
    # Iterator

    public function rewind() : void
    {
        $this->position = 0;
    }

    /**
     * Gets the current item
     *
     * @return mixed
     */
    public function current()
    {
        return $this->items[$this->position];
    }

    public function key() : int
    {
        return $this->position;
    }

    public function next() : void
    {
        ++$this->position;
    }

    public function valid() : bool
    {
        return isset($this->items[$this->position]);
    }
}
