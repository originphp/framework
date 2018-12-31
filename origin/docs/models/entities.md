# Entities

An entity is a single row from the database. Using find first or find all will return either a entity object or an array of entity objects.


```php
  $article = $this->Article->find('first');

  echo $article->title;

  # hasOne & BelongsTo are singular are singular camelCase
  $author = $article->author;

  echo $author->name;

  # hasMany and hasAndBelongsToMany will be plural camelCase
  foreach($article->comments as $comment){
    echo $comment->text;
  }

```

## Isset

`isset($article->title)`

or

`$article->hasProperty('title')`


## Set

`article->title = $title`

or

`article->set('title',$title)`

You can also set many properties at once

```php

  $data = ['title' => 'Article Title','status'=>'draft'];
  $article->set($data);

```

## Get

`$title = $article->title`

or

`$title = article->get('title')`

You can also get many properties at once

`$extracted = article->get(['title','status'])`

## Property Exists

Sometimes you need to see if a property is set regardless if it is null.

`$bool = $article->propertyExists('id')`
