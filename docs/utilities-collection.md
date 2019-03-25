# Collections

You can create a collection using arrays or the results such as from a find all (which is a different type of collection, `Origin\Model\Collection`.

To create a collection:

```php
    use Origin\Utility\Collection;
    $collection = new Collection($array);
```

There is also a a helper function which you can use.

```php
    $collection = collection($array);
```

## Collections

After you have finished manipulating the data you can use `toArray` or `toList` to convert the collection. Some methods return a boolean (e.g. every) or number (e.g median, average etc.), however most will return a new collection, which then can be chained through other methods.

## Iteration Methods

### Extract

Extracts a single column from a collection to create a list. You can use dot notation.

```php
    $collection = collection($books);
    $authors = $collection->extract('author.name');
    $list = $authors->toList();
```

You can also use a callback function :

```php
    $collection = collection($books);
    $books = $collection->extract(function ($book) {
      return $book->name . ' written by ' . $book->author->name;
    });
    $list = $books->toList();
```

### Each

Go through each item of the collection. You should note that each does not modify data. If you want to modify data then use `map`.

```php
    $collection = new Collection($books);
    $collection->each(function ($value, $key) {
        echo "{$key} - {$value}";
    });
```

### Map

This will iterate through each item in the collection and pass value through a callback
which can modify the data and return it, creating a new collection in the process.

```php
    $collection = new Collection([
        'a'=>1,'b'=>2,'c'=>3
        ]);

    // using a callable must return a value
    $plusOneCollection = $collection->map(function ($value, $key) {
        return $value + 1;
    });

```

### Combine

Creates a new collection using keys and values.

```php
    $collection = new Collection($results);
    $combined = $collection->combine('id', 'name'); 
    $array = $combined->toArray(); //[1=>'Tom','2'=>'James']
```

Results from combine can also be grouped by a third key.

```php
    $collection = new Collection($results);
    $result => $collection->combine('id', 'name','profile'); 
    $array = $result->toArray(); // ['admin' => [1=>'tom',2=>'tim']]
```

### Chunk

Chunks a collection into multiple parts

```php
  $collection = new Collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]);
  $chunks = $collection->chunk(5);
  $array = $chunks->toArray();  // [[1,2,3,4,5],[6,7,8,9,10],[11,12]];
```

## Filter Methods

### Filter
Filters results using a callback function
```php
    $collection = new Collection($books);
    $inStock = $collection->filter(function ($book) {
        return $book->in_stock ===  true;
    });
```
### Reject
This is the inverse of filter.
```php
    $collection = new Collection($books);
    $notInStock = $collection->reject(function ($book) {
        return $book->in_stock ===  true;
    });
```

### Every
Run truth tests on every item in the collection.

```php
    $collection = new Collection($books);
    $allBooksInStock = $collection->every(function ($book) {
        return $book->in_stock > 0;
    });
    if($allBooksInStock){
        ...
    }
```

### Some
Check to see if at least one item matches the filter

```php
    $collection = new Collection($books);
    $anyThingInStock = $collection->some(function ($book) {
        return $book->in_stock > 0;
    });
    if($anyThingInStock){
        ...
    }
```

## Sorting

### SortBy

Sorts a collection by a field or callback.
To sort by a field, and you can use dot notation.
```php
    $collection = new Collection($books);
    $sortedCollection = $collection->sortBy('author.name');
``` 
To sort by a callback.

```php
    $collection = new Collection($books);
    $sortedCollection = $collection->sortBy(function ($book) {
        return $book->author->name . '-' . $book->name;
    });
```  
The sortBy method accepts 3 arguments, with the first argument being the path or a callback.

The second argument is the direction,which can be either `SORT_DESC` or `SORT_ASC`. 

The third argument depends upon the data and the same flags used by [PHP Sort](http://php.net/manual/en/function.sort.php), which include:

- `SORT_NUMERIC` - for numbers
- `SORT_STRING` - for strings
- `SORT_NATURAL` - for natural ordering

## Aggregation

### Min

Gets the first item with the smallest value. 

```php
    $collection = new Collection($authors);
    $author = $collection->min('rating');
``` 

To sort by a callback.

```php
    $collection = new Collection($books);
    $author = $collection->min(function ($book) {
        return $book->author->score;
    });
```

### Max

Gets the first item with the smallest value. 

```php
    $collection = new Collection($books);
    $author = $collection->max('author.rating');
``` 

To sort by a callback.

```php
    $collection = new Collection($books);
    $author = $collection->max(function ($book) {
        return $book->author->score;
    });
```

## Counting

### SumOf

Gets the sum from a field or callback.

```php
    $collection = new Collection($books);
    $inStock = $collection->sumOf('in_stock');
``` 

To get the sum using a callback

```php
    $collection = new Collection($books);
    $points = $collection->sumOf(function ($book) {
        return $book->author->rating;
    });
```

### Avg

Gets the average value from a field or callback.

```php
    $collection = new Collection($books);
    $avgRating = $collection->avg('author.rating');
``` 

To get the average value using a callback

```php
    $collection = new Collection($books);
    $avgRating = $collection->avg(function ($book) {
        return $book->author->rating;
    });
```

### Median

Gets the median value from a field or callback.

```php
    $collection = new Collection($books);
    $median = $collection->median('author.rating');
``` 

To get the median value using a callback

```php
    $collection = new Collection($books);
    $median = $collection->median(function ($book) {
        return $book->author->rating;
    });
```

### Count
This a function to count items in the collection, it is useful when working with other collection methods such as take or chunk.
```php
    $collection = new Collection($books);
    $count = $collection->count();
``` 

### CountBy

Counts by a field and value, and results are grouped.

```php
    $collection = new Collection($books);
    $counts = $collection->countBy('author.type'); // ['famous'=>10,'new'=>20]
``` 
You can also use a callback.

```php
    // ['odd'=>2,'even'=>3]
    $collection = new Collection($books);
    $counts = $collection->countBy(function ($book) {
        return $book->id % 2 == 0 ? 'even' : 'odd';
    }); 
```

## Grouping

### GroupBy

Groups results by a field or callback.

```php
    $collection = new Collection($books);
    $grouped = $collection->groupBy('author.type');
    $array = $grouped->toArray();
``` 
You can also use a callback.

```php
    $collection = new Collection($books);
    $grouped = $collection->groupBy(function ($book) {
        return $book->id % 2 == 0 ? 'even' : 'odd';
    });
    $array = $grouped->toArray();
```

## Inserting Data

### Insert

Inserts a value into a path for each item in the collection.

```php
    $collection = new Collection($books);
    $newCollection = $collection->insert('author.registered',true);
    $books = $newCollection->toArray();
```

Taking the same example, I will chain it using the helper function. This can be done with any method
that returns a new collection.

```php
    $books = collection($books)->insert('author.registered',true)->toArray();
``` 

## Other

### Take

Take a number of items from a collection.

```php
    $collection = new Collection($books);
    $firstLot = $collection->take(10);
    $secondLot = $collection->take(10);
``` 
