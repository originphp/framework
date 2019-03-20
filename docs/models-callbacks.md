# Callbacks

## beforeFind

This is called before any find operation. The `query` array passed to the `beforeFind` callback contains information such as conditions, fields etc.

If `beforeFind` returns `false` then the find operation is canceled.  Return `true` or the modified `query`.

```php
public function beforeFind(array $query = []){
  if(!parent::beforeFind($query)){
    return false;
  }
  $query['conditions']['published'] = true;
  return $query;
}
```

## afterFind

This is called after any find operation. Results from the find operation are passed to this function and needs to return back the results. Here you can modify data or carry out other tasks.

```php
public function afterFind($results){
  $results = parent::afterFind($results);
  foreach($results as $article){
    $this->doSomething($article);
  }
  return $results;
}
```

## beforeValidate

This is called just before data is validated and must return true. Use this callback to modify data before validate

```php
public function beforeValidate(Entity $entity){
  if(!parent::beforeValidate($entity)){
    return false;
  }
  $this->doSomething($entity);
  return true;
}
```

## afterValidate

This is called after the data has been validated, even if validation fails this callback is executed. You can get the validation errors from the entity by calling `hasErrors` on the entity.

```php
public function afterValidate(Entity $entity,bool $success){
    parent::afterValidate($entity,$success);
    $this->doSomething();
}
```


## beforeSave
This is called before any save operation. The `options` array is the same as the one passed to the save method. The filter must return `true` or saving will stopped.

```php
public function beforeSave(Entity $entity,array $options=[]){
  if(!parent::beforeSave($entity,$options)){
    return false;
  }
  $entity->slug = Slugger::slug($entity->title);
  return true;
}
```

## afterSave
This is called after a save operation. If a record was created then `created` is set to `true`.

```php
public function afterSave(Entity $entity,bool $created,array $options =[]){
  parent::afterSave($entity,$created,$options);
  if($created){
    $this->doSomething($entity->id);
  }
  else{
    $this->doSomethingElse($entity->id);
  }
}
```

## beforeDelete
This is called just before a record is deleted must return `true`. Use this callback to carry out tasks before a record is deleted.

Note: When saving (including creating) or deleting a record the primary key can be found on the id property of the model.

```php
public function beforeDelete(Entity $entity, bool $cascade){
  if(!parent::beforeDelete($entity,$cascade))){
    return false;
  }
  $this->doSomething($this->id);
  return true;
}
```

## afterDelete

This is called after a record is deleted.

```php
public function afterDelete(Entity $entity,bool $success){
    parent::afterDelete();
    $this->doSomething($this->id);
}
```
