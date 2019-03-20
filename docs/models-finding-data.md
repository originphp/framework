# Finding Data

The examples below will relate to the following models and because the tables are setup using conventions
we don't need to pass parameters.

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

With `get` you can get a single record, also called an `Entity`.

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

### Query

If you need to carry out a raw SQL query the you can use the `query` method.

```php
$result = $this->Article->query('SELECT name from articles');
```

To securely pass values when using sql statements, pass an array with key value pairs.

```php
$result = $User->query('SELECT name FROM users WHERE id = :id',['id'=>1234]);
```