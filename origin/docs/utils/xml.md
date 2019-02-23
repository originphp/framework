# XML

## Create XML from an array

You always must pass an array with 1 root element.

To set attributes, prefix the key with @. You can also set a value of an element using @.

````php
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
````

This will return the following:

````xml
    <post category="how tos">
        <id>12345</id>
        <title>How to create an XML block</title>
        <body><![CDATA["A quick brown fox jumps of a lazy dog."]]></body>
        <author>
            <name>James</name>
        </author>
    </post>
````

For data which needed to be wrapped in CDATA, call `Xml::cdata($string)`.

You can also pass options when creating XML from an array.

````php
    $xml = Xml::fromArray($data,[
            'version' => '1.0',
            'encoding' => 'UTF-8',
            'pretty' => true
            ]);
````    

Sometimes you might need to repeat the tags in XML, so you can do so like this.

````php
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
````

Which will output this:

````xml
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
````

## Create an Array from XML

````php
    $xml= '<?xml version="1.0" encoding="utf-8="?><note><to>You</to><from>Me</from><heading>Reminder</heading>  <description>Buy milk</description></note>';
    $xml= Xml::toArray($xml);
````