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

class XmlTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalidArray()
    {
        $this->expectException(XmlException::class);

        $data = [];
        Xml::fromArray($data);
    }
    public function testInvalidXml()
    {
        $this->expectException(\Exception::class);
        $result = Xml::toArray('<foo/><bar/>');
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
            'book' => [
                '@id' => 256,
                'name' => 'book name',
                '@' => 'text value'
            ]
        ];
   
        $needle = '<book id="256">text value<name>book name</name></book>';
        $this->assertContains($needle, Xml::fromArray($data));

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

        $data = [
            'book' => [
                'xmlns:' => 'http://www.w3.org/1999/xhtml',
                'title' => 'Its a Wonderful Day'
            ]
            ];
        $needle = '<book xmlns="http://www.w3.org/1999/xhtml"><title>Its a Wonderful Day</title></book>';
    
        $this->assertContains($needle, Xml::fromArray($data));

        $data = [
            'tag' => [
                'item'=> [
                    'one',
                    'two',
                    'three'
                ]
            ]
                ];

        $needle = '<tag><item>one</item><item>two</item><item>three</item></tag>';
        $this->assertContains($needle, Xml::fromArray($data));

        $data = [
            'student:record' => [
                'xmlns:student' => 'https://www.originphp.com/student',
                'student:name' => 'James',
                'student:phone' => '07986 123 4567'
            ]];

        $needle = '<student:record xmlns:student="https://www.originphp.com/student"><student:name>James</student:name><student:phone>07986 123 4567</student:phone></student:record>';
      
        $this->assertContains($needle, Xml::fromArray($data));

        $data = [
            'book' => [
                'xmlns:' => 'urn:loc.gov:books',
                'xmlns:isbn' => 'urn:ISBN:0-395-36341-6',
                'title' => 'Cheaper by the Dozen',
                'isbn:number' => '1568491379'
            ]
        ];

        $needle = '<book xmlns="urn:loc.gov:books" xmlns:isbn="urn:ISBN:0-395-36341-6"><title>Cheaper by the Dozen</title><isbn:number>1568491379</isbn:number></book>';
        $this->assertContains($needle, Xml::fromArray($data));
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

        $expected = [
            'book' => [
                'xmlns:' => 'http://www.w3.org/1999/xhtml',
                'title' => 'Its a Wonderful Day'
            ]
            ];

        $string = '<?xml version="1.0" encoding="UTF-8"?>
        <book xmlns="http://www.w3.org/1999/xhtml"><title>Its a Wonderful Day</title></book>';
       
        $this->assertEquals($expected, Xml::toArray($string));

        $expected = [
            'student:record' => [
                'xmlns:student' => 'https://www.originphp.com/student',
                'student:name' => 'James',
                'student:phone' => '07986 123 4567'
            ]];

        $string = '<?xml version="1.0" encoding="UTF-8"?>
        <student:record xmlns:student="https://www.originphp.com/student"><student:name>James</student:name><student:phone>07986 123 4567</student:phone></student:record>';
                
        $this->assertEquals($expected, Xml::toArray($string));

        $expected = [
            'book' => [
                'xmlns:' => 'urn:loc.gov:books',
                'xmlns:isbn' => 'urn:ISBN:0-395-36341-6',
                'title' => 'Cheaper by the Dozen',
                'isbn:number' => '1568491379'
            ]
        ];

        $string = '<?xml version="1.0" encoding="UTF-8"?>
        <book xmlns="urn:loc.gov:books" xmlns:isbn="urn:ISBN:0-395-36341-6"><title>Cheaper by the Dozen</title><isbn:number>1568491379</isbn:number></book>';

        $this->assertEquals($expected, Xml::toArray($string));
    }


    public function testCdata()
    {
        $data = [
            'note' => [
                'description' => Xml::cdata('This is a test.')
            ]
            ];
        $string = Xml::fromArray($data);
       
        $expected = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<note><description>&lt;![CDATA["This is a test."]]&gt;</description></note>'."\n";
        $this->assertEquals($expected, $string);
        
        $this->assertEquals($data, Xml::toArray($string));
    }

    public function testToArrayException()
    {
        $this->expectException(\Exception::class);
        Xml::toArray('no xml here');
    }
}
