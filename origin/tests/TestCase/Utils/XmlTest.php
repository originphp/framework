<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Utils;

use Origin\Utils\Xml;
use Origin\Utils\Exception\XmlException;

class DateTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $result = Xml::create('post', [
            '@category' => 'how tos',
            'id' => 12345,
            'title' => 'How to create an XML block',
            'body' =>  Xml::cdata('A quick brown fox jumps of a lazy dog.')
           ]);

        $expected = '<post category="how tos"><id>12345</id><title>How to create an XML block</title><body>&lt;![CDATA["A quick brown fox jumps of a lazy dog."]]&gt;</body></post>';
        $this->assertEquals($expected, $result);
    }
    public function testFromArray()
    {
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
      
        $expected = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<post category="how tos"><id>12345</id><title>How to create an XML block</title><body>&lt;![CDATA["A quick brown fox jumps of a lazy dog."]]&gt;</body><author><name>James</name></author></post>'. "\n";
        $this->assertEquals($expected, Xml::fromArray($data));

        $data = [
            'case' => [
                '@id' => 256,
                'name' => 'case name',
                '@' => 'case value'
            ]
        ];
   
        $expected = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<case id="256">case value<name>case name</name></case>'."\n";
        $this->assertEquals($expected, Xml::fromArray($data));

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
       
        $expected = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<charges><charge><amount>10</amount><description>Shipping</description></charge><charge><amount>35</amount><description>Tax</description></charge></charges>'."\n";
        $this->assertEquals($expected, Xml::fromArray($data));
    }

    /**
     * @depends testFromArray
     */
    public function testFromArrayOptions()
    {
        $options = [
            'version' => '2.0',
            'encoding' => 'ISO-8859-1',
            'pretty' => true
        ];
        $data = [
            'note' => [
                'to' => 'You',
                'from' => 'Me',
                'heading' => 'Reminder',
                'description' => 'Buy milk'
                ]
            ];

        $expected = "<?xml version=\"2.0\" encoding=\"ISO-8859-1\"?>\n<note>\n  <to>You</to>\n  <from>Me</from>\n  <heading>Reminder</heading>\n  <description>Buy milk</description>\n</note>\n";
        $this->assertEquals($expected, Xml::fromArray($data, $options));
    }

    public function testToArray()
    {
        $string = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<post category="how tos"><id>12345</id><title>How to create an XML block</title><body>&lt;![CDATA["A quick brown fox jumps of a lazy dog."]]&gt;</body><author><name>James</name></author></post>'. "\n";
        $expected = [
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
  
        $this->assertEquals($expected, Xml::toArray($string));

        $expected = [
            'case' => [
                '@id' => 256,
                'name' => 'case name',
                '@' => 'case value'
            ]
        ];
   
        $string = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<case id="256">case value<name>case name</name></case>'."\n";
        $this->assertEquals($expected, Xml::toArray($string));

        $expected = [
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
       
       
        $string = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<charges><charge><amount>10</amount><description>Shipping</description></charge><charge><amount>35</amount><description>Tax</description></charge></charges>'."\n";
        $this->assertEquals($expected, Xml::toArray($string));
    }

    public function testToArrayException()
    {
        $this->expectException(XmlException::class);
        Xml::toArray('no xml here');
    }
}
