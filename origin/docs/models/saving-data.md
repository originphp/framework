# Saving data

To save a record, it needs to be an [entity object](entities.md). 

You can use the `newEntity` or `patchEntity` methods in the model to create the entity object for you.

When creating the entity you can do so like this from within the model:

```php
    $entity = $this->newEntity();
    $entity->name = 'foo';
```

Or like this

```php
  $entity = $this->newEntity(['name'=>'foo']);
```

From the controller it would be

```php
    $entity = $this->MyModel->newEntity(['name'=>'foo']);
```

The PatchEntity method is used for taking an existing entity and patching it with data from an array. When this entity is saved, only the modified fields will be saved the database.

From the controller patch entity is used like this:

```php
    $entity = $this->MyModel->patchEntity($existing,$this->request->data);
```

## save(Entity $entity,array $options = [])

This function is to save one record for the current model. Optionally you can pass an array of options.

`$this->Article->save(Entity $entity,$options)`

```php
  $article->title = 'How to save models using objects';
  $this->Article->save($article);
```

You can also pass a options array with any of the following keys.

- `validate` default is `true` but you can set to `false` to disable validation
- `callbacks` default is `true` but you can set to `false` to not call the [callback](callbacks.md) methods such `beforeSave` or `afterSave`. You can also set to either `before` or `after` to only call the `beforeSave` or `afterSave`.
- `transaction` default is `true`. Saves the record as a single transaction.

```php
  $options = [
    'callbacks' => false,
    'validate' => false
  ];

  $this->Article->save($article,$options);
```

Sometimes you will not have data as an entity and you want  save this.

## saveField($primaryKey, string $name,string $value,array $options = [])

If you have all ready loaded a record, then just use save, and this will save the modified fields. However, when you need to update a field in the database without having to load the record, then you can use `saveField`. The options that you can pass
are the same as when using the `save` this is because the `saveField` method basically creates the object and then saves through `save`.

`$this->Article->saveField($primaryKey, $fieldName, $fieldValue,$options)`

```php
  $this->Article->saveField(1024,'title','New Title');
```


## saveMany($data,array $options = [])

This function is to save multiple records for the current model. Optionally you can pass an array of options as used in the `save` method.

`$this->Article->saveMany($data)`

This basically loops over the array of entities and runs the `save` method.

```php
  $this->Article->saveMany($articles)
```

The options array with any of the following keys.

- `validate` default is `true` but you can set to `false` to disable validation
- `callbacks` default is `true` but you can set to `false` to not call the [callback](callbacks.md) methods such `beforeSave` or `afterSave`. You can also set to either `before` or `after` to only call the `beforeSave` or `afterSave`.
- `transaction` default is `true`. If enabled it will save data as a single transaction, and if any failures happen it will rollback the changes.


## updateAll(array $data,array $conditions)

To update one or multiple records in a single call. Note callbacks are not triggered.

`$this->Article->updateAll($data,$conditions)`

```php
  $data = [
    'checked' => true,
    'status' => 'Verified'
  ];

  $conditions = [
    'status' => 'Pending'
  ];

  $this->Article->updateAll($data,$conditions);
```

## saveAssociated($data, array $options = [])

You can save records with with associated data such as `hasOne`,`BelongsTo` and `hasMany`.

`$this->Article->saveAssociated($data,$options)`

```php
  $article = [
    'title' => 'How to save data with associated data',
    'author' => [          // belongsTo
        'name' => 'Jane Smith',
      ],
    'approval' => [     // hasOne
        'approved_by' => 'Tony'
      ],
    'comments' => [    // Has Many
        ['text' => 'foo'],
        ['text' => 'bar'],
      ]
  ];
  $entity = $this->Article->newEntity($article);
  $this->Article->saveAssociated($entity);
```
## Saving HABTM records

Saving `hasAndBelongsToMany` data is done through the normal model save.

You can save HABTM in two ways

- using the `id` primary key of the associated model

```php

  $data = array(
    'id' => 1,
    'tags' => array(
      array('id' => 1),
      array('id' => 2)
    ));
  $entity = $this->Article->newEntity($data);
  $this->Article->save($data);

```

- using the `displayField` of the associated model

```php

  $data = array(
    'id' => 1,
    'tags' => array(
      array('name' => 'New'),
      array('name' => 'Featured')
    );
  $entity = $this->Article->newEntity($data);
  $this->Article->save($data);

```
Saving data through this method is a quick and easy method to save `hasAndBelongsToMany` data. However callbacks are only called when creating the associated model, in this example the Tag model.

If you wish to save extra data to the join table or use callbacks then you should make sure the table has a unique primary key field and then save and delete data directly from the join model.

```php

  $articlesTag = $this->Article->ArticlesTag->newEntity();

  $articleTag->article_id = 123
  $articleTag->tag_id = 456;
  $articleTag->status = 'new'

  $this->Article->ArticlesTag->save($entity);

```
