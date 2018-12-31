# Models Overview

To access models, you can do so from the controller

`$results = $this->Model->find('all')`

If you need to access a model from the controller you will need to call `loadModel`.

`$MyModel = $this->loadModel('MyModel')`

From models you can access associated models (`hasOne`,`hasMany`,`belongsTo`,`hasAndBelongsToMany`)

`Model->AssociatedModel->find('all')`;

So from a controller it would be like this

`$results = $this->Model->AssociatedModel->find('all')`

From within a model

```php
  class User extends AppModel
  {
    public $hasOne = array('Profile');

    public function doSomething(){
      $this->Profile->delete(1);
    }
  }
```

Models should only be used from within controllers or other models.

If you need to load any model then you can use the the following code
```php
  use Origin\Model\ModelRegistry;
  
  $User = ModelRegistry::get('User');
```
