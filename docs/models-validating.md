# Validation

## How to use validation rules

Validation rules are defined in the `initialize` method of your model.

You can define a rule as a string, rule array, or array with multiple rules.

```php
  class User extends AppModel
  {
    public function initialize(array $config)
    {
        parent::initialize($config);
        // String
        $this->validate('password','notBlank');
        // single rule
        $this->validate('username', [
            'rule' => 'notBlank',
            'message' => 'This is required'
          ]);
          // multiple rules
         $this->validate('email', [
            ['rule'=>'notBlank'],
            ['rule'=>'email']
        ]);
    }
  }
```

A validation rule array consists of the following keys:

- *rule* - this is the name of the rule to run
- *message* - error message to display if validation fails
- *on* - default is `null`. You can also set to `create` or `update` to only check validation rule when a record is created or updated.
- *required* - default is `false`. If this is set to true then this means the data must include the key regardless if it is empty or not. For example you might want an owner id to always be present when creating a record. If you set required, then this means 

## Validation Rules

When setting the rules the name is usually as a string, however some validation rules offer extra arguments, so then you would pass the rule name with the arguments as a single array.

```php
  $this->validate('field',[
    'rule' => ['range',1,100]
  ]);
```

### alphaNumeric

```php
  $this->validate('username',[
    'rule' => 'alphaNumeric',
    'message' => 'Error only letters and numbers are allowed'
    ]);
```

### boolean

Checks if a value is a boolean true or false.

```php
  $this->validate('active',[
    'rule' => 'boolean'
    ]);
```


### custom (Function)

You can create your own functions in model which can be used to validate.

```php
public function initialize(array $config){
  $this->validate('dob','birthdate');
}


public function birthdate($value){
  if($value){
    return true;
  }
  return false;
}

```
You can also pass more arguments

```php
public function initialize(array $config){
  $this->validate('status',[
    'rule' => ['statusCheck','go']
  ]);
}


public function statusCheck($value, $arg1){
  return $value1 === $arg1;
}

```


### custom (Regex)

You use custom regex patterns to validate

```php
  $this->validate('code',[
      'rule' => '/^[a-zA-Z]+$/',
      'message' => 'Letters only'
    ]);
```

### date

Validates a date using a format compatible with the php date function. The default date format is `Y-m-d`.

```php
  $this->validate('sent',[
    'rule' => 'date'
    'message' => 'Invalid date format'
  ]);
```

or

```php
  $this->validate('sent',[
    'rule' => ['date', 'Y-m-d'],
    'message' => 'Invalid date format'
  ]);
```

### datetime

Validates a datetime using a format compatible with the php date function. The default datetime format is `Y-m-d H:i:s`.


```php
  $this->validate('sent',[
      'rule' => 'datetime'
      'message' => 'Invalid datetime format'
    ]);
```

or

```php
  $this->validate('sent',[
       'rule' => ['datetime', 'Y-m-d H:i:s'],
        'message' => 'Invalid datetime format'
    ]);
```

### decimal

Checks if a value is a decimal. The value must have a decimal place in it.

```php
  $this->validate('amount',[
    'rule' => 'decimal',
    'message' => 'Invalid amount'
  ]);
```

### email

Checks that a value is a valid email address, works with UTF8 email address.

```php
  $this->validate('email',[
    'rule' => 'email'
    'message' => 'Enter a valid email address'
  ]);
```


### equalTo

Checks that a value equals another value

```php
 $this->validate('level',[
        'rule' => ['equalTo','someString']
        'message' => 'Value must be someString'
  ]);
```

### extension

Checks that a value matches an array of extensions
```php
 $this->validate('filename',[
    'rule' => ['extension',['csv','txt']]
      'message' => 'Only csv or text files can be uploaded'
  ]);
```

### inList

Checks that a value is in a list.

```php
 $this->validate('status',[
    'rule' => ['inList',['draft','new','authorised']]
    'message' => 'Invalid status'
  ]);
```
The default is case sensitive search, if you want to the search to be case insensitive then you will need to pass `true` as the third option.

```php
 $this->validate('status',[
    'rule' => ['inList',['draft','new','authorised'],true]
    'message' => 'Invalid status'
  ]);
```

### ip

Checks that a value is a valid ip address.

```php
 $this->validate('ip_address',[
      'rule' => 'ip'
    'message' => 'Enter a valid ip address'
  ]);
```

### isUnique

Checks that a field value is unique in the database.

```php
 $this->validate('id',[
   'rule' => 'isUnique'
    'message' => 'ID field is not unique'
  ]);
```

You can also check multiple values

```php
 $this->validate('email',[
      'rule' => ['isUnique',array('username','email')],
      'message' => 'Email and username are not unique'
  ]);
```

### maxLength

Checks if string is less than or equals to the max length.

```php
 $this->validate('username',[
    'rule' => ['maxLength',12],
    'message' => 'Username is too long'
  ]);
```

### minLength

Checks if string has a minimum amount of characters.

```php
 $this->validate('password',[
    'rule' => ['minLength',8],
    'message' => 'Password is insecure, at least 8 characters required'
  ]);
```

### notBlank

Checks that a value is not empty and has anything other than whitespaces.

```php
 $this->validate('name',[
     'rule' => 'notBlank',
      'message' => 'You must enter something'
  ]);
```

### notEmpty

Checks that a value is not empty.

```php
 $this->validate('name',[
     'rule' => 'notEmpty',
     'message' => 'You must enter something'
  ]);
```

### numeric

Checks that a value is a number.

```php
 $this->validate('employees',[
    'rule' => 'numeric',
    'message' => 'Is not a valid number'
  ]);
```

### range

Checks that a value is in a range.

```php
 $this->validate('number',[
    'rule' => ['range',10,20],
    'message' => 'Enter a number between 10 and 20'
  ]);
```

### time
Validates a time using a format compatible with the php date function. The default time format is `H:i:s`.

```php
 $this->validate('number',[
   'rule' => 'time',
    'message' => 'Invalid time format'
  ]);
```
### url

Checks that a value is a valid url.

By default a valid url has to have the protocol e.g. `https://www.google.com`.

```php
 $this->validate('number',[
   'rule' => 'url',
    'message' => 'Invalid URL make sure you include https://'
  ]);
```

If you want to consider `www.google.com` a valid url (without the protocol)  then you would do so like this.

```php
 $this->validate('number',[
    'rule' => ['url', false]
    'message' => 'Enter without http://'
  ]);

   
```
