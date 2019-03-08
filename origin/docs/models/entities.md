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

From within the controller you will want to create entity from request data, you do this by accessing the model methods `newEntity` and `patchEntity`;

````php 
  $user = $this->User->newEntity($this->request->data);
````

You can also just get a blank entity for the model by not passing an array when calling `newEntity`;

````php 
  $user = $this->User->newEntity();
  $user->name = 'james'
  $user->email = 'james@example.com'
````

If you want to create multiple entities then you can do as follows:

````php 
  $entities = $this->User->newEntities([
    ['name'=>'James'],
    ['name'=>'John']
  ]);
````

This will create an array with two entities.

If you are editing an existing record, then use `patchEntity`. Only fields that have been modified will be
saved. The field will be classed as modified even if the value stays the same, since we are going patch the existing 
entity with the data, in this case from the request.

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

## Extract

You can also get many properties at once

`$extracted = article->extract(['title','status'])`

## Property Exists

Sometimes you need to see if a property is set regardless if it is null.

`$bool = $article->propertyExists('id')`

## Errors

Validation errors are contained within the entities.

To get all errors

````php
  $errors = $entity->errors();
````

To get error(s) for a field

````php
  $errors = $entity->getError('first_name');
````

To set errors manually

````php
  $entity->setError('email','invalid email address');
````

## Other Methods

### reset()
This resets the modified property and any validation errors.

### modified()
Gets a list of fields that were modified.

### name()
Gets the model name of the entity.