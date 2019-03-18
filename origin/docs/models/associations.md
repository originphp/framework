# Associations

## Overview

The examples below are based if working with a User model.

>  Models assume that the primary key is called `id` and that foreign keys end with `_id`. If you do not follow that convention then you will need to set the `foreignKey` for each relationship and the `associationForeignKey` for any `hasAndBelongsToMany` relationships.

Related models can be accessed from the current model.

`$notes = $this->RelatedModel->find('all',$params)`

When working from the controller, you would access like this

`$results = $this->Model->RelatedModel->find('all')`

To load associated records for model, in the find options you pass an array of models that you want to get, and providing that models have been setup properly (See below) then data will be loaded.

````php
  $user = $this->User->get($id, [
            'associated'=>['Task','Email'=>['fields'=>$fields],'Contact'=>['associated'=>$nestedModels]]
            ]);
````
You can pass options for each model to be contained, these will overide what was set with the functions below. You can also load nested associated data, by passing the contain option for each model.

## Has One
This is one-to-one relationship. The other model contains the foreign key.
e.g. User has one Profile, the foreign key is in the other table, this would be

`Profile.user_id`

You can define the relationship using the `hasOne` method.

`hasOne(string $alias,array $options = [])`

Create a method in your model called `initialize` and this will be called when the model is constructed.

```php
  class User extends AppModel
  {
    public function initialize(array $config){
      parent::initialize($config);
      $this->hasOne('Profile');
    }
  }
```

You can also pass an options array with any of the following keys.

- `className` is the name of the class that you want to load.
- `foreignKey` the foreign key in the other model. The default value would be the underscored name of the current model suffixed with '\_id'.
- `conditions` an array of additional conditions to the join
- `fields` an array of fields to return from the join model, by default it returns all
- `order` a string or array of how to order the result
- `dependent` default is `false`, if set to true when delete is called with cascade it will related records.

```php
  class User extends AppModel
  {
    public function initialize(array $config){
      parent::initialize($config);
      $this->hasOne('Profile', array(
          'className' => 'UserProfile',
          'foreignKey' => 'user_profile_id',
          'conditions' => array('UserProfile.active'=>true),
          'fields' => array('User.id','User.name','UserProfile.id','UserProfile.status')
          'order' => array('User.name ASC'),
          'dependent' => true)
          );
    }
  }
```

## Belongs To
This is a many-to-many relationship. The current model contains the foreign key. e.g. Profile belongs to User. The foreign key is found in Profile table `Profile.user_id`.

`belongsTo(string $alias,array $options = [])`

Create a method in your model called `initialize` and this will be called when the model is constructed.

```php
  class Profile extends AppModel
  {
    public function initialize(array $config){
      parent::initialize($config);
      $this->belongsTo('User');
    }
  }
```

To understand whether you use `hasOne` or `belongsTo`, you need to look at in which table the foreign key is.

You can also pass an options array with any of the following keys.

- `className` is the name of the class that you want to load.
- `foreignKey` the foreign key in the current model.  The default value would be the underscored name of the other model suffixed with '\_id'.
- `conditions` an array of additional conditions to the join
- `fields` an array of fields to return from the join model, by default it returns all
- `order` a string or array of how to order the result
- `type` default is `LEFT`, this is the join type used to fetch the associated record.

```php
  class Profile extends AppModel
  {
    public function initialize(array $config){
      parent::initialize($config);
      $this->belongsTo('SuperUser',array(
          'className' => 'User',
          'foreignKey' => 'user_id',
          'conditions' => array('SuperUser.email !='=> null),
          'fields' => array('SuperUser.id','SuperUser.name','Profile.id','Profile.name')
          'order' => array('SuperUser.group','SuperUser.name ASC'),
          'dependent' => true
          'type' => 'INNER'
        ));
    }
  }
```

## Has Many
The other model contains the foreign key. Similar to has one, but will have more than one record.

e.g. User has many Emails, the foreign key is in the other table, this would be `Email.user_id`

`hasMany(string $alias,array $options = array())`

Create a method in your model called `initialize` and this will be called when the model is constructed.

```php
  class User extends AppModel
  {
    public function initialize(array $config){
      parent::initialize($config);
      $this->hasMany('Email');
    }
  }
```

You can also pass an options array with any of the following keys.

- `className` is the name of the class that you want to load.
- `foreignKey` the foreign key in the other model. The default value would be the underscored name of the current model suffixed with '\_id'.
- `conditions` an array of additional conditions to the join
- `fields` an array of fields to return from the join model, by default it returns all
- `order` a string or array of how to order the result
- `dependent` default is `false`, if set to true when delete is called with cascade it will related records.
- `limit` default is `null`, set a value to limit how many rows to return
- `offset` if you are using limit then set from where to start fetching

```php
  class User extends AppModel
  {
    public function initialize(array $config){
      parent::initialize($config);
      $this->hasMany('SentEmail',array(
          'className' => 'Email',
          'foreignKey' => 'user_id',
          'conditions' => array('SentEmail.sent'=> true),
          'fields' => array('SentEmail.id','SentEmail.subject','SentEmail.body','SentEmail.created')
          'order' => array('SentEmail.created ASC'),
          'dependent' => true
        ));
    }
  }
```

## Has And Belongs To Many (HABTM)
The HABTM requires a separate join table, in alphabetical order with each model pluralized. A User `hasAnd BelongsToMany` Tags (inverse is also true). The table would be called `tags_users`. It needs to contain the `user_id` and `tag_id`. The join model, called `TagsUser` will be created dynamically, but if you need to more control or functionality, then you can simply create the model.

```sql
  CREATE TABLE tags_users (
      user_id INT NOT NULL,
      tag_id INT NOT NULL
  );
```


Create a method in your model called `initialize` and this will be called when the model is constructed.

```php
  class User extends AppModel
  {
    public function initialize(array $config){
      parent::initialize($config);
      $this->hasAndBelongsToMany('Tag');
    }
  }
```

You can also pass an options array with any of the following keys.

- `className` is the name of the class that you want to load.
- `joinTable` the name of the table used by this relationship
- `with` the name of the model which uses the join table
- `foreignKey` - the foreign key in the current model. The default value would be the underscored name of the other model suffixed with '\_id'.
- `associationForeignKey` the foreign key in the other model. The default value would be the underscored name of the other model suffixed with '\_id'.
- `conditions` an array of additional conditions to the join
- `fields` an array of fields to return from the join model, by default it returns all
- `order` a string or array of how to order the result
- `dependent` default is `false`, if set to true when delete is called with cascade it will related records.
- `limit` default is `null`, set a value to limit how many rows to return
- `offset` if you are using limit then set from where to start fetching
- `unique` default is `true`, when adding records, all other relationships are deleted first. So it assumes one save contains all the joins. You can also set this to `keepExisting`, this should be set if you will store other data in the join table, as it wont delete relationships which it is adding back.

```php
  class User extends AppModel
  {
    public function initialize(array $config){
      parent::initialize($config);
      $this->hasAndBelongsToMany('Tag',array(
      'className' => 'Tag',
      'joinTable' => 'users_tags',
      'with' => 'UsersTags'
      'foreignKey' => 'user_id',
      'associationForeignKey' => 'tag_id',
      'fields' => array('User.name','User.email','Tag.title','Tag.created')
      'order' => array('Tag.created ASC'),
      'unique' => true
      'limit' => 50
    }
  }
```
