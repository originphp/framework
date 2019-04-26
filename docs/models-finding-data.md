# Finding Data

The examples below will relate to the following models and because the tables are setup using conventions, we don't need to pass parameters.

```php
class Article extends AppModel
{
  public function initialize(array $config){
    $this->hasOne('Author');
    $this->hasMany('Comment');
  }
}
```

```php
class Author extends AppModel
{
  public function initialize(array $config){
    $this->belongsTo('Article');
  }
}
```

```php
class Comment extends AppModel
{
  public function initialize(array $config){
    $this->belongsTo('Article');
  }
}
```

## Retrieving data from the database

To retrieve data from the database, the model class provides a function called find which will read the data
from the database and then transform it into objects which you can work with.

### Getting a single record using the primary key

With `get` you can get a single record, also called an `Entity`. See the [Entities guide](models-entities.md) for information on these objects.

```php
$article = $this->Article->get(1000);
echo $article->title;
```
When you use `get` if the record is not found, it will throw a `NotFoundException`.

### First Finder

The first finder will return a single record as an entity object, the difference between this and get is that it will not throw an exception if a record is not found. In fact the get method uses the `first` finder.

```php
$article = $this->Article->find('first');
echo $article->title;
echo $article->author->name;
```

You can also pass an array of options, which will be explained in the *Find Options* chapter. If no result is found it will return `null`.

If you need the result to be an array form then you can call `toArray` on the result.

### All Finder

The all finder will return multiple records as a special collection object.

```php
$articles = $this->Article->find('all');
foreach($articles as $article){
  echo $article->title;
  foreach($article->comments as $comment){
    echo $comment->description;
  }
}
```

You can also pass an array of options. If no results are found it will return an empty array.

If you need the results to be an array form then you can call `toArray` on the results object.


### List Finder

The list finder will return an array of selected data. If you specify a 3rd field the data will be grouped.

```php
    $list = $this->Article->find('list',['fields'=>['id']]); // ['a','b','c']
    $list = $this->Article->find('list',['fields'=>['id','title']]); // ['a'=>'b']
    $list = $this->Article->find('list',['fields'=>['id','title','category']]); // ['c'=>['a'=>'b']
```
### Count Finder

The count finder will return a count based upon criteria that you have supplied.

```php
$count = $this->Article->find('count',[
    'conditions'=>[
        'owner_id' => 1234,
        'status'=> ['Published']
        ]
    );
```

## Conditions
To set conditions for the `find` or `get` you need to pass an array with the key `conditions`, which itself should be an array. When you fetching associated data, if you don't add an alias prefix to the field name it will be assumed that the condition is for the current model.

```php
$conditions = ['id'=>1234];
$result = $this->Article->find('first',['conditions'=>$conditions]);
```

### Equals

You can use either a string or an array if you want to search multiple.

```php
  $conditions = ['title' => 'How to write an article'] // title = "How to write an article"
  $conditions = ['Author.name' => 'James']; // Author.name = "James"
  $conditions = ['author_id' => [1000,1001,1002];  // author_id IN (1000,1001,1002)
```

### Not Equals

You can use either a string or an array if you want to search multiple.

```php
  $conditions = ['title !=' => 'How to write an article'] // title != "How to write an article"
  $conditions = ['Author.name !=' => 'James']; // Author.name = "James"
  $conditions = ['author_id !=' => [1000,1001,1002];  // author_id NOT IN (1000,1001,1002)
```


### Comparing

To compare two fields

```php
 $conditions = ['Article.created  = Article.modified'];
```

### Arithmetic

To check field values, such as greater, less than etc.

```php
 $conditions = ['rating >' => 5];
 $conditions = ['rating <' => 10];
 $conditions = ['rating >=' => 5];
 $conditions = ['rating <=' => 10];
```

### Between

To use between

```php
 $conditions = ['rating BETWEEN' => [5,10]];
 $conditions = ['rating NOT BETWEEN' => [5,10]];
```

### Like

```php
 $conditions = ['Author.name LIKE' =>'Tony%'];
 $conditions = ['Author.name NOT LIKE' =>'%Tom%'];
```

### And,OR and NOT

To create more complex queries you can use and or and not.

```php
$conditions = [
    'Author.name' => 'James',
    'OR' => [
      'Aritcle.title LIKE' => 'how to%',
      'Article.status' => 'Published'
    ]
]
```
This would generate something like this this:

```sql
Author.name = 'James' AND (Aritcle.title LIKE 'how to%' OR Aritcle.status = 'Published')
```

If you wanted to search using the same fields you can put each condition in its own array.

```php
$conditions = [
    'Author.name' => 'James',
    'OR' => [
      ['Aritcle.title LIKE' => 'how to%'],
      ['Aritcle.title LIKE' => '100 Ways to%'],
    ]
]
```

You can also nest multiple OR, AND queries using arrays. 

Lets say you want to search by article title or author:

```php
  $conditions = [
    ['OR' => [
      ['title LIKE' => '%how to%'],
      ['title LIKE' => '%100 ways to%'],
      ]],
    ['OR' => [
        ['Author.name LIKE' => '%tony%'],
        ['Author.name LIKE' => '%claire%'],
    ]],
    ];
```

## Find Options

### Conditions

The conditions key is what is used to generate the sql queries. You should always try to add the alias to the field.

```php
$conditions = ['Article.id'=>1234];
$result = $this->Article->find('first',['conditions'=>$conditions]);
```

### Fields

By default all fields are returned for each model, even if you don't use them. You can reduce the load on the server
by selecting just the fields that you need.

```php
$result = $this->Article->find('first',['fields'=>['id','title','author_id']]);
```

To use also pass DISTINCT, MIN and MAX etc. When using those database functions remember to include AS then a unique
field name.

```php
$conditions = ['fields'=>['DISTINCT (Author.name) AS author_name','title']];
```

### Order

Use this option to order the results by fields. Make sure you add the alias prefix e.g. `Article.` to the field if you are working with associated data. The order option can be a string or an array.

```php
$result = $this->Article->find('all',[
  'order'=>'Article.created DESC'
  ]);

$result = $this->Article->find('all',[
  'order'=>['Article.title','Article.created ASC'
  ]]); // ORDER BY Article.title,Article.created ASC
```
You can set the default sort order for a model in the model property `order`, any calls to find without order will use this as the natural order.

### Group

To run a group by query, any aliased fields that don't exist in the table will be added as a property to the
entity of the current model regardless if it took the data from another table.

```php
$result = $this->Article->find('all',[
  'fields'=>['COUNT(*) as total','category'],
  'group'=>'category'
  ]);
```

This will return something like this

```php
[0] => Origin\Model\Entity Object
        (
            [category] => 'How To'
            [total] => 2
        )
```

### Limit

Limit is basically what it says it does, it limits the number of results.

```php
$result = $this->Article->find('first',['limit'=>5]);
```

## Eager Loading Associations

Associated records are not fetched unless you tell find to do so. Providing you have set up the relationships e.g. `hasMany`,`hasOne`,`belongsTo`,`hasAndBelongsToMany`, you will be able to fetch related data by passing array with the models that you want to fetch data for.

```php
$result = $this->Article->find('first',[
  'associated'=>['Author','Comment']
  ]);
```

By default all fields for each the associated models will be fetched (or if you have configured the association to return only certain fields by default) unless you tell it otherwise.

NOTE: If you limit the fields that are returned, you must always ensure that `foreignKey` must be present, if not the final results wont include the records.

```php
$result = $this->Article->find('first',[
  'associated'=>[
    'Author'=>[
      'fields'=>['id','name','email']
      ]
    ]
  ]);
```

## Joining Tables

Sometimes you might want to do your own joins, this can easily be done by using the `joins` option when finding. This option should be an array of arrays, since you do multiple joins.

```php
  $conditions['joins'][] = [
    'table' => 'authors',
    'alias' => 'Author',
    'type' => 'LEFT' , // this is defualt,
    'conditions' => [
      'Author.id = Article.author_id'
    ]
   ];
```

## Disabling Callbacks

By default callbacks are enabled, you can disable them by passing false, then the `beforeFind` and `afterFind` will not be called.

```php
$result = $this->Article->find('first',['callbacks'=>false]);
```

## Finding out if a record exists

If you need to find a record exists with a primary key you can use the `exists` method.

```php
$result = $this->Article->exists(1024);
```

## Running Raw SQL queries

If you need to carry out a raw SQL query the you can use the `query` method.

```php
$result = $this->Article->query('SELECT name from articles');
```

To securely pass values when using sql statements, pass an array with key value pairs.

```php
$result = $this->User->query('SELECT name FROM users WHERE id = :id',['id'=>1234]);
```