# Entities

An entity is a single row from the database. Using find first or find all will return either a entity object or a collection of entity objects. 

Note: The collection object from a find all is not the same as the collection utility, it is a lighter version of this, you can still pass the results from find all to a collection object.

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

From within the controller you will want to create entity from request data, you do this by accessing the model methods `new` and `patch`. Arrays of data passed through these methods go through a marshaling process.

```php 
  $user = $this->User->new($this->request->data());
```

To get a blank entity for a model do this:

```php
  $user = $this->User->new();
  $user->name = 'james'
  $user->email = 'james@example.com'
```

If you want to create multiple entities from form data it should be like this:

```php 
  $formData = [
    ['name'=>'James'],
    ['name'=>'John']
  ];
  $entities = $this->User->newEntities($formData);
```

This will return a collection object with two entities.

If you are editing an existing record, then use `patch`. Only fields that have been modified will be
saved. The field will be classed as modified even if the value stays the same, since we are going patch the existing  entity with the data, in this case from the request.

```php 
  $user = $this->User->patch($existingEntity,$this->request->data());
```

## Isset

```php
$result = isset($article->title);
$result = $article->has('title')
```



## Set
```php
$article->title = $title;
$article->set('title',$title)
```

You can also set many properties at once

```php
$article->set([
  'title' => 'Article Title',
  'status'=>'draft'
  ]);
```

## Get

```php
$title = $article->title;
$title = $article->get('title');
```

## Errors

Validation errors are contained within the entities.

To get all errors

```php
  $errors = $entity->errors();
```

To get error(s) for a field

```php
  $errors = $entity->errors('first_name');
```

To set errors manually

```php
  $entity->invalidate('email','invalid email address');
```

## Other Methods

### reset

This resets the modified property and any validation errors.

### modified

Gets a list of fields that were modified.

### name

Gets the model name of the entity.

### properties

Gets a list of properties from the object

### toArray

Converts the entity into an array

### toJson

Converts the entity a into json.