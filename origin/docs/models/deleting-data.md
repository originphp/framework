# Deleting Your Data

## delete($id,bool $cascade = true, $callbacks = true)

- `id` is the unique id for the record.
- `cascade` is by default set to true. When enabled this will also delete `hasOne`,`hasMany` and `hasAndBelongsToMany` data.
- `callbacks` default is `true`. This will trigger `beforeDelete` and `afterDelete` callbacks. If `beforeDelete` callback returns false then the rest the operation is cancelled.

```php
  $this->Article->delete(1024);
```

## deleteAll(array $conditions,bool $cascade = true, $callbacks = true)
- `conditions` are the conditions to match records.
- `cascade` is by default set to true. When enabled this will also delete `hasOne`,`hasMany` and `hasAndBelongsToMany` data.
- `callbacks` default is `true`. This will trigger `beforeDelete` and `afterDelete` callbacks. If `beforeDelete` callback returns false then the rest the operation is cancelled.

```php

  $conditions = array(
    'status' => 'archived'
  );

  $this->Article->deleteAll($conditions);
```
