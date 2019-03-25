# XML

## Create XML from an array

You always must pass an array with 1 root element.

To set attributes, prefix the key with @. You can also set the text value of an element using @.

```php
    $data = [
        'post' => [
            '@category' => 'how tos', // to set attribute use @
            'id' => 12345,
            'title' => 'How to create an XML block',
            'body' =>  Xml::cdata('A quick brown fox jumps of a lazy dog.'),
            'author' => [
                'name' => 'James'
                ]
            ]
    ];
        
    $xml = Xml::fromArray($data);
```

This will return the following:

```xml
    <post category="how tos">
        <id>12345</id>
        <title>How to create an XML block</title>
        <body><![CDATA["A quick brown fox jumps of a lazy dog."]]></body>
        <author>
            <name>James</name>
        </author>
    </post>
```

For data which needed to be wrapped in CDATA, pass the data through `Xml::cdata($string)`.

You can also pass options when creating XML from an array.

```php
    $xml = Xml::fromArray($data,[
            'version' => '1.0',
            'encoding' => 'UTF-8',
            'pretty' => true
            ]);
```    

Sometimes you might need to repeat the tags in XML, so you can do so like this.

```php
 $data = [
    'charges' => [
        'charge' => [
            [
                'amount' => 10,
                'description' => 'Shipping',
            ],
            [
                'amount' => 35,
                'description' => 'Tax',
            ],
          ]
        ]
    ];
```

Which will output this:

```xml
    <?xml version="1.0" encoding="UTF-8"?>
    <charges>
        <charge>
            <amount>10</amount>
            <description>Shipping</description>
        </charge>
        <charge>
            <amount>35</amount>
            <description>Tax</description>
        </charge>
    </charges>
```

Here is an example of setting attributes (prefix the key with @) and text values (set the key to @).

```php
    $data = [
        'task' => [
            '@id' => 128,
            'name' => 'Buy milk',
            '@' => 'some text'
        ]
    ];
```
Which gives this:

```xml
    <?xml version="1.0" encoding="UTF-8"?>
    <task id="128">some text<name>Buy milk</name></task>
```

## Create an Array from XML

You can also create an array from the XML using the `toArray` method.

```php
    $xml = '<?xml version="1.0" encoding="utf-8"?><note><to>You</to><from>Me</from><heading>Reminder</heading>  <description>Buy milk</description></note>';
    $array = Xml::toArray($xml);
```

# Namespaces

The xml utility also works with namespaces.

To set a generic namespace set the key `xmlns:`.

```php
     $data = [
        'book' => [
            'xmlns:' => 'http://www.w3.org/1999/xhtml',
            'title' => 'Its a Wonderful Day'
            ]
        ];
    $xml = Xml::fromArray($data);
```
This will output this:

```xml
    <?xml version="1.0" encoding="UTF-8"?>
    <book xmlns="http://www.w3.org/1999/xhtml">
    <title>Its a Wonderful Day</title>
    </book>
```

You can setup custom namespaces like this:


```php
  $data = [
        'student:record' => [
            'xmlns:student' => 'https://www.originphp.com/student',
            'student:name' => 'James',
            'student:phone' => '07986 123 4567'
        ]
    ];
```

Which will give you this

```xml
    <?xml version="1.0" encoding="UTF-8"?>
    <student:record xmlns:student="https://www.originphp.com/student">
        <student:name>James</student:name>
        <student:phone>07986 123 4567</student:phone>
    </student:record>
```

Lets take an example from [w3.org](https://www.w3.org/TR/xml-names/) and re-create this using an
array.

So this is what we want to produce:

```xml
<book xmlns='urn:loc.gov:books'
      xmlns:isbn='urn:ISBN:0-395-36341-6'>
    <title>Cheaper by the Dozen</title>
    <isbn:number>1568491379</isbn:number>
</book>
```

To do this in an array (you could use the toArray method if you have the existing XML) set it up as
follows.

```php
  $data = [
        'book' => [
            'xmlns:' => 'urn:loc.gov:books',
            'xmlns:isbn' => 'urn:ISBN:0-395-36341-6',
            'title' => 'Cheaper by the Dozen',
            'isbn:number' => '1568491379' 
        ]
    ];
```