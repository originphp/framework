# Validation

## How to use validation rules

You define validation rules in your model in the `validate` property.

You can define a rule as a string, rule array, or array with multiple rules.

```php
  class User extends AppModel
  {
    public $validate = array(
      'username' => 'alphaNumeric', // string
      'email' => array(  // single rule
        'rule' => 'email',
        'message' => 'You must enter a valid Email',
        'required' => true
      ),
      'password' => array(    // multiple rules per field
          'rule1' => array(
            'rule'=>'notBlank'
          ),
          'rule2' => array(
            'rule'=>'alphaNumeric'
          )
      )
    );
  }
```

A validation rule array consists of the following keys:

- *rule* - this is the name of the rule to run
- *message* - error message to display if validation fails
- *on* - default is `null`. You can also set to `create` or `update` to only validate when a record is created or updated.
- *required* - default is `false`. If this is set to true then this means the data array must include the key regardless if it is empty or not.

```php
  array(
    'field' => array(  
      'rule' => 'rule name',
      'message' => 'error message',
      'required' => true
    )  
  );

```

## Validation Rules

When setting the rules the name is usually as a string, however some validation rules offer extra arguments, so then you would pass the rule name with the arguments as a single array.

```php
  'rule' => ['name',$arg1,$arg2]
```

### alphaNumeric

```php
  public $validate = array(
    'username' => array(
        'rule' => 'alphaNumeric',
        'message' => 'Error only letters and numbers are allowed'
    ));
```

### Boolean

Checks if a value is a boolean true or false.


### Custom (Regex)

You use custom regex patterns to validate

```php
  public $validate = array(
    'code' => array(
        'rule' => '/^[a-zA-Z]+$/',
        'message' => 'Letters only'
    ));
```

### Date

Validates a date using a format compatible with the php date function. The default date format is `Y-m-d`.

```php
  public $validate = array(
    'sent' => array(
        'rule' => 'date'
        'message' => 'Invalid date format'
    ));
```

or

```php
  public $validate = array(
    'sent' => array(
        'rule' => array('date', 'Y-m-d'),
        'message' => 'Invalid date format'
    ));
```

### Datetime

Validates a datetime using a format compatible with the php date function. The default datetime format is `Y-m-d H:i:s`.

```php
  public $validate = array(
    'sent' => array(
        'rule' => 'datetime'
        'message' => 'Invalid datetime format'
    ));
```

```php
  public $validate = array(
    'sent' => array(
        'rule' => array('datetime', 'Y-m-d H:i:s'),
        'message' => 'Invalid date format'
    ));
```
### Decimal

Checks if a value is a decimal. The value must have a decimal place in it.
```php
  public $validate = array(
    'amount' => array(
        'rule' => 'decimal'
        'message' => 'Invalid date format'
    ));
```

### Email

Checks that a value is a valid email address, works with UTF8 email address.
```php
  public $validate = array(
    'email' => array(
        'rule' => 'email'
        'message' => 'Enter a valid email address'
    ));
```


### EqualTo

Checks that a value equals another value

```php
  public $validate = array(
    'level' => array(
        'rule' => array('equalTo','someString')
        'message' => 'Value must be somestring'
    ));
```

### Extension

Checks that a value matches an array of extensions
```php
  public $validate = array(
    'level' => array(
        'rule' => array('extension',['csv','txt'])
        'message' => 'Only csv or text files can be uploaded'
    ));
```

### InList

Checks that a value is in a list.

```php
  public $validate = array(
    'status' => array(
        'rule' => array('inList',['draft','new','authorised'])
        'message' => 'Invalid status'
    ));
```
The default is case sensitive search, if you want to the search to be case insensitive then you will need to pass `true` as the third option.

```php
  public $validate = array(
    'status' => array(
        'rule' => array('inList',['draft','new','authorised'],true)
        'message' => 'Invalid status'
    ));
```

### ip

Checks that a value is a valid ip address.

```php
  public $validate = array(
    'ip_address' => array(
        'rule' => 'ip'
        'message' => 'Enter a valid ip address'
    ));
```

### isUnique

Checks that a field value is unique in the database.

```php
  public $validate = array(
    'id' => array(
        'rule' => 'isUnique'
        'message' => 'ID field is not unique'
    ));
```

You can also check multiple values

```php
  public $validate = array(
    'email' => array(
        'rule' => array('isUnique',array('username','email')),
        'message' => 'Email and username are not unique'
    ));
```

### maxLength

Checks if string is less than or equals to the max length.

```php
  public $validate = array(
    'username' => array(
          'rule' => array('maxLength',12),
          'message' => 'Username is too long'
    ));
```

### minLength

Checks if string has a minimum amount of characters.

```php
  public $validate = array(
    'password' => array(
          'rule' => array('minLength',8),
          'message' => 'Password is insecure, at least 8 characters required'
    ));
```

### notBlank

Checks that a value is not empty and has anything other than whitespaces.

```php
  public $validate = array(
    'name' => array(
          'rule' => 'notBlank',
          'message' => 'You must enter something'
    ));
```

### notEmpty

Checks that a value is not empty.

```php
  public $validate = array(
    'name' => array(
          'rule' => 'notBlank',
          'message' => 'You must enter something'
    ));
```

### numeric

Checks that a value is a number.

```php
  public $validate = array(
    'age' => array(
          'rule' => 'numeric',
          'message' => 'Is not a valid number'
    ));
```

### range

Checks that a value is in a range.

```php
  public $validate = array(
    'level' => array(
          'rule' => array('range',10,20),
          'message' => 'Enter a number between 10 and 20'
    ));
```

### time
Validates a time using a format compatible with the php date function. The default time format is `H:i:s`.

```php
  public $validate = array(
    'alert' => array(
        'rule' => 'time',
        'message' => 'Invalid time format'
    ));
```
### url

Checks that a value is a valid url.

By default a valid url has to have the protocol e.g. https://www.google.com.

```php
  public $validate = array(
    'website' => array(
        'rule' => 'url',
        'message' => 'Invalid URL make sure you include https://'
    ));
```

If you want to consider www.google.com a valid url (without the protocol)  then you would do so like this.

```php
  public $validate = array(
    'website' => array(
        'rule' => array('url', false)
        'message' => 'Enter without http://'
    ));
```
