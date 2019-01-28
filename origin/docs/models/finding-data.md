# Retrieving Your Data

## Model::get($id, array $options=[])

This will find a record by id or throw a `NotFoundException`.

You can pass options array as used by find (see below);

## find($type,array $options = [])

`find($type,$options)` is what you will use to find your data, depending upon the type, it will then call the appropriate finder.

The types are `first`,`all`,`list` and `count`.

When working with results from a find the data for each result will be structured as this for an Object:

```php
  echo $article->title;

  # hasOne & BelongsTo are singular are singular camelCase
  $author = $article->author;

  echo $author->name;

  # hasMany and hasAndBelongsToMany will be plural camelCase
  foreach($article->comments as $comment){
    echo $comment->description;
  }

```

You can convert result object also called a `Entity` by calling the `toArray()` method.

`$article->toArray()`

Arrays of results are structured the same but in an array.

```php
  echo $article['title'];

  $author = $article['author']; // hasOne & BelongsTo are singular camelCase

  echo $author['name'];

  # hasMany and hasAndBelongsToMany will be plural camelCase
  foreach($article['comments'] as $comment){
    echo $comment['description'];
  }

```

When using find `first` if no records are found, it will return a `null` value. Find `all` will return an empty array if no results are found.

Here is an example of a `options` array for a common use.

```php
array(
  'conditions' => array('Article.status' => 'New'),
  'fields' => array('Article.id','Article.title'),
  'order' => array('Article.title','Article.created ASC'),
  'group' => array('Article.category'),
  'limit' => 10,
);

```

- **fields** is an array of fields that you want to return in the query

- **order** is either a string or an array of how you want the data to be ordered.

- **group** is for the database group query results.

- **limit** this sets how many rows are returned.

- **callbacks** - If this is set to true, before or after then it will call the model callbacks.

- **contain** - An array of models that you want to load associated data for. You can also pass the model as key and array of config, e.g ['Tag'=>['fields'=>$field,'conditions'=>$conditions]]

  - *-1* does no joins at all.
  - *0* fetches `belongsTo` and `hasOne` associated records
  - *1* will also fetch the `hasMany` associated records
  - *2* will fetch the `hasMany` records with their `belongsTo` and `hasOne` information.

**offset** - Select from which record to find the data, this is used with limit.

**page** - Instead of using offset you can use page, this works with the limit setting. This will automatically calculate the offset.

**joins** - An array of join settings to join a table.

```php
array(
  'table' => 'authors',
  'alias' => 'Author',
  'type' => 'LEFT', // default
  'conditions' => array(
    'Author.id = Article.author_id'
    )
  );
```

## Find First

`find(‘first’,$options)` returns one record.

Below are some examples:

`$article = $this->Article->find('first');`

`$article = $this->Article->find('first',array('conditions'=>array('id'=>1024)));`


## Find All

`find(‘all’,$options)` returns multiple records.

Below are some examples:

`$articles = $this->Article->find('all');`

`$articles = $this->Article->find('first',array('conditions'=>array('published !='=>1)));`

## Find Count

`find(‘count’,$options)` returns a count of records

Below are some examples:

`$count = $this->Article->find('count');`

`$count = $this->Article->find('count',array('conditions'=>array('group'=>array('New','Featured','Starred'))));`


## Find List

`find(‘list’,$options)` will return a array list based upon the number of fields selected. If you don't specify fields, then the model `displayField` will be used.

`$articles = $this->Article->find('list',array('fields'=>array('title')));`

This will return an array with just values.

```php
  array(
    [0] => 'Top 10 PHP frameworks',
    [1] => 'Hot PHP frameworks',
    [3] => 'The Best PHP Framework'
  );
```


`$articles = $this->Article->find('list',array('fields'=>array('slug','title')));`

This will return an array with using the first field as the key and the second field as the value.

```php
array(
  'top-10-php-frameworks' => 'Top 10 PHP frameworks',
  'hot-php-frameworks' => 'Hot PHP frameworks',
  'the-best-php-framework' => 'The Best PHP Framework'
);
```

You can also group lists passing three fields.

`$articles = $this->Article->find('list',array('fields'=>array('slug','title','status')));`

This will return an array with using the first field as the key and the second field as the value.

```php
array(
  'Published' => array(
    'top-10-php-frameworks' => 'Top 10 PHP frameworks',
    'hot-php-frameworks' => 'Hot PHP frameworks',
  ),
  'Draft' => array(
    'the-best-php-framework' => 'The Best PHP Framework'
  )
);
```

## Conditions

Use arrays of conditions to build queries.

### Equals

`$conditions = array('Post.title' => 'Needle');`

`$conditions = array('Post.title' => array('Needle 1','Needle 2'));` // Same as IN

`$conditions = array('Contract.signed' => null);`

To set multiple conditions on a single field wrap them in an array.

```php
array(
  array('User.name LIKE' => '%John%'),
  array('User.name LIKE' => '%James%')
)
```

### Not Equals

`$conditions = array('Post.title !=' => 'Needle');`

`$conditions = array('Post.title !=' => array('Needle 1','Needle 2'));` // Same as IN

`$conditions = array('Post.signed !=' => null);`

### Comparison (multiple fields)

`array('Article.created  = Article.modified');`

### Comparison (arithmetic):

`$conditions = array('Survey.number >' => 3);`

`$conditions = array('Survey.number <' => 5);`

`$conditions = array('Survey.number >=' => 7);`

`$conditions = array('Survey.number <=' => 9);`

### BETWEEN

`$conditions = array('Survey.number BETWEEN' => array(10,15));`

`$conditions = array('Survey.number NOT BETWEEN' => array(10,15));`

### LIKE

`$conditions = array('User.name LIKE' => 'j%');`

`$conditions = array('User.name NOT LIKE' => '%th');`

### AND/OR/NOT

You can also use AND, OR , NOT to create more complicated queries.

For example, lets say you wanted to generate a query like
`( Article.title LIKE 'How To%' OR Article.title LIKE '%Computers%' ) AND Article.published = 1`

Then you would setup the array like this:

```php
array(
  'OR' => array(
    array('Article.title LIKE' => 'How to%'),
    array('Article.title LIKE' => '%Computers%')
  ),
  'Article.published' => 1
);

```

## Queries

To run a SQL query on the model you can run query method with optional `params`. This can be used for running custom database queries. If there is a result set it will return it.

`Model->query($sql)`

```php

$sql = 'SELECT name from users';
$result = $User->query($sql);

$sql = 'SELECT name FROM users WHERE id = :id';
$values = array('id'=>'1234')
$result = $User->query($sql,$values);

```
