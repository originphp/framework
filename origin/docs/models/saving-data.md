# Saving data

## save($ data,array $options = array())

This function is to save one record for the current model. Data can be an array or object. Optionally you can pass  `options`.

`$this->Article->save($data,$options)`

```php
$article = array(
    'title' => 'How to save models using arrays'
  );

$this->Article->save($article)
```

or if you are working with objects then

```php
$article->title = 'How to save models using objects';

$this->Article->save($article);
```


You can also pass a options array with any of the following keys.

- `validate` default is `true` but you can set to `false` to disable validation
- `callbacks` default is `true` but you can set to `false` to not call the [callback](callbacks.md) methods such `beforeSave` or `afterSave`. You can also set to either `before` or `after` to only call the `beforeSave` or `afterSave`.
- `transaction` default is `true`. Saves the record as a single transaction.

```php
$options = array(
  'callbacks' => false,
  'validate' => false
);

$this->Article->save($article,$options);
```

## saveField($primaryKey, string $name,string $value,array $options = array())

If you have all ready loaded a record, then just use save, and 
this will save the modified fields. However, when you need to update a field in the database without having
to load the record, then you can use save field.

`$this->Article->saveField($primaryKey, $fieldName,$fieldValue,$options)`

```php
$this->Article->saveField(1024,'title','New Title');
```


## saveMany($data,array $options = array())

This function is to save multiple records for the current model. Optionally you can pass `options`.

`$this->Article->saveMany($data)`

This basically loops over the data and runs the `save` method.

```php
$articles array(
    array('tile'=>'Saving many part # 1'),
    array('tile'=>'Saving many part # 1'),
  );

$this->Article->saveMany($articles)
```

You can also pass a options array with any of the following keys.

- `validate` default is `true` but you can set to `false` to disable validation
- `callbacks` default is `true` but you can set to `false` to not call the [callback](callbacks.md) methods such `beforeSave` or `afterSave`. You can also set to either `before` or `after` to only call the `beforeSave` or `afterSave`.
- `transaction` default is `true`. If enabled it will save data as a single transaction, and if any failures happen it will rollback the changes.


## updateAll(array $data,array $conditions)

To update one or multiple records in a single call. Note callbacks are not triggered.

`$this->Article->updateAll($data,$conditions)`

```php
$data = array(
  'checked' => true,
  'status' => 'Verified'
);

$conditions = array(
  'status' => 'Pending'
);

$this->Article->updateAll($data,$conditions);
```

## saveAssociated($data, array $options = array())

You can save records with with associated data such as `hasOne`,`BelongsTo` and `hasMany`.

`$this->Article->saveAssociated($data,$options)`

```php
$article = array(
  'title' => 'How to save data with associated data',
  'author' => array(          // belongsTo
      'name' => 'Jane Smith',
    ),
   'approval' => array(     // hasOne
      'approvedBy' => 'Tony'
     ),
   'comments' => array(     // Has Many
       array('text' => 'foo'),
       array('text' => 'bar'),
     )
);
```
## saving HABTM

Saving `hasAndBelongsToMany` data is done through the normal model save.

You can save HABTM in two ways

- using the `id` primary key of the associated model

```php

$data = array(
  'id' => 1,
  'tags' => array(
    array('id' => 1),
    array('id' => 2)
  );

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

$this->Article->save($data);

```
Saving data through this method is a quick and easy method to save `hasAndBelongsToMany` data. However callbacks are only called when creating the associated model, in this example the Tag model.

If you wish to save extra data to the join table or use callbacks then you should make sure the table has a unique primary key field and then save and delete data directly from the join model.

```php

$data = array(
  'article_id' => 123,
  'tag_id' => 456,
  'status' => 'new'
);

$this->Article->ArticlesTag->save($data);

```
