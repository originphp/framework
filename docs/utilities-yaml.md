# YAML

The YAML utility is for reading (parsing) and writing YAML files. Note: The YAML utility does not cover the complete specification, it is designed to read and write configuration files, and data from the database so that it can be read and edited in user friendly way.

## Create a YAML from an array

```php
use Origin\Utility\Yaml;
$employees = [
    ['name'=>'Jim','skills'=>['php','mysql','puppeteer']],
    ['name'=>'Amy','skills'=>['ruby','ruby on rails']],
];
$yaml = Yaml::fromArray($employees );
```

That will return the following

```yaml
- name: Jim
  skills:
    - php
    - mysql
    - puppeteer
- name: Amy
  skills:
    - ruby
    - ruby on rails
```

Lets look what happens when we convert a record from the bookmarks demo app.

```php
 $bookmark = $this->Bookmark->get(1,[
            'associated'=>[
                'User','Tag'
                ]
            ]);
$string = Yaml::fromArray($bookmark->toArray()); 

```

This will create the following YAML string:

```yaml
id: 1
user_id: 1
title: OriginPHP
description: The PHP framework for rapidly building scalable web applications.
url: https://www.originphp.com
category: Computing
created: 2018-12-28 15:25:34
modified: 2019-05-02 13:08:44
user: 
  id: 1
  name: Demo User
  email: demo@example.com
  password: $2y$10$/clqxdb.aWe43VXDUn8tA.yxKbWHZT3rN7gqITFaj32PZHI3.DkzW
  dob: 1999-12-28
  created: 2018-12-28 15:24:13
  modified: 2018-12-28 15:24:13
tags: 
  - id: 1
    title: Framework
    created: 2019-03-07 18:45:43
    modified: 2019-03-07 18:45:43
    bookmarksTag: 
      bookmark_id: 1
      tag_id: 1
  - id: 2
    title: PHP
    created: 2019-03-07 18:45:43
    modified: 2019-03-07 18:45:43
    bookmarksTag: 
      bookmark_id: 1
      tag_id: 2
tag_string: Framework,PHP
```

## Read YAML from a string

To create an array from a YAML string

```php
$employee =  'name: Tom
position: developer
skills:
    - php
    - mysql
    - js
addresses:
    - street: 14 some road
      city: london
    - street: 21 some avenue
      city: leeds
summary:
    this is a text description
    for this employee';
$array = Yaml::toArray($employee);
/*
Array
(
    [name] => Tom
    [position] => developer
    [skills] => Array
        (
            [0] => php
            [1] => mysql
            [2] => js
        )

    [addresses] => Array
        (
            [0] => Array
                (
                    [street] => 14 some road
                    [city] => london
                )

            [1] => Array
                (
                    [street] => 21 some avenue
                    [city] => leeds
                )

        )

    [summary] => this is a text description for this employee
)
*/
```
