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

When you want to create an entity from an array, you can do this  in the constructor.

````php 
  use Origin\Model\Entity;

  $entity = new Entity(['name'=>'Jon']);

````

From within the controller you will want to create entity from request data


````php 
  $user = $this->User->newEntity($this->request->data);
````

If you are editing an existing record, then use patchEntity. Only fields from request data are saved.

````php 
  $user = $this->User->patchEntity($existingEntity,$this->request->data);
````


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

## Errors

Validation errors are contained within the entities.

You can get and set them using `errors`

To get all errors
`$entity->errors();`

To get errors for a field

`$entity->errors('first_name');`

To set errors 
````php
  $entity->errors('email','invalid email address');
  $entity->errors('password',['alphanumeric only','min length must be 5']);
````

   