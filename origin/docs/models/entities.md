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

From within the controller you will want to create entity from request data, you do this by accessing the model methods `newEntity` and `patchEntity`. Arrays of data passed through these methods go through a marshalling process -  date, datetime and number fields will be parsed according to user locale.

````php 
  $user = $this->User->newEntity($this->request->data);
````

To get a blank entity for a model do this:

````php 
  $user = $this->User->newEntity();
  $user->name = 'james'
  $user->email = 'james@example.com'
````

If you want to create multiple entities from form data it should be like this:

````php 
  $formData = [
    ['name'=>'James'],
    ['name'=>'John']
  ];
  $entities = $this->User->newEntities($formData);
````

This will create an array with two entities.

If you are editing an existing record, then use `patchEntity`. Only fields that have been modified will be
saved. The field will be classed as modified even if the value stays the same, since we are going patch the existing  entity with the data, in this case from the request.

````php 
  $user = $this->User->patchEntity($existingEntity,$this->request->data);
````

*Important*: Any user submitted data should pass through the `newEntity` or `patchEntity` function, since the data will go through the marshalling process and fields like dates,time, and numbers are parsed according to user locale.

You can disable parsing like this when creating a new Entity

````php 
  $entity = $this->User->newEntity($data,['parse'=>false]);
````
And for patching an existing entity

````php 
  $combined = $this->User->patchEntity($existingEntity,$this->request->data,['parse'=>false]);
````

## Isset

`isset($article->title)`

or

`$article->has('title')`


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