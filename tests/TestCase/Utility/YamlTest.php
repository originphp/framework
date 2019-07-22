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

namespace Origin\Test\Utility;

use Origin\Utility\Yaml;
use Origin\Utility\Exception\YamlException;

class YamlTest extends \PHPUnit\Framework\TestCase
{
    public function testFromArrayScalar()
    {
        $student = [
            'id' => 1234,
            'name' => 'james',
            'date' => '2019-05-05',
            'boolean' => false,
        ];
        $yaml = Yaml::fromArray($student);
        $expected = <<< EOT
id: 1234
name: james
date: 2019-05-05
boolean: false
EOT;
        $this->assertContains($expected, $yaml);
    }
    public function testFromArrayCollection()
    {
        $student = [
            'id' => 1234,
            'address' => [
                'line' => '458 Some Road
                Somewhere, Something', // multi line
                'city' => 'london',
            ],
            
        ];
        $yaml = Yaml::fromArray($student);
       
        $expected = <<< EOT
id: 1234
address: 
  line: | 458 Some Road
                Somewhere, Something
  city: london
EOT;
        $this->assertContains($expected, $yaml);
    }

    public function testFromList()
    {
        $students = ['tony','nick'];
        $yaml = Yaml::fromArray($students);
        $expected = <<< EOT
- tony
- nick
EOT;
        $this->assertContains($expected, $yaml);
    }
    public function testFromChildList()
    {
        $students = [
            ['name' => 'tony','phones' => ['1234-456']],
            ['name' => 'nick','phones' => ['1234-456','456-4334']],
        ];
        $yaml = Yaml::fromArray($students);
        $expected = <<< EOT
- name: tony
  phones: 
    - 1234-456
- name: nick
  phones: 
    - 1234-456
    - 456-4334
EOT;

        $this->assertContains($expected, $yaml);
    }

    public function testFromArrayMultiCollections()
    {
        $students = [
            'id' => 1234,
            'name' => 'tony',
            'addresess' => [
                ['street' => '1234 some road','city' => 'london'],
                ['street' => '546 some avenue','city' => 'london'],
            ],
        ];
        $yaml = Yaml::fromArray($students);
        $expected = <<< EOT
id: 1234
name: tony
addresess: 
  - street: 1234 some road
    city: london
  - street: 546 some avenue
    city: london
EOT;
        $this->assertContains($expected, $yaml);
    }

    public function testPlainScalarMultiline()
    {
        $yaml = <<< EOF
multi:
  a
  b
  c
  d
name: test
EOF;
        $this->assertEquals('a b c d', Yaml::toArray($yaml)['multi']);
    }

    public function testFromArrayMultiLevel()
    {
        $data = [
            'services' => [
                'app' => [
                    'build' => '.',
                    'depends_on' => [
                        'db',
                    ],
                ],
                'memcached' => [
                    'image' => 'memcached',
                ],
            ],
            'volumes' => [
                'mysql' => 'abc', // leaving this blank is a problem. works with docker. but cant parse it
            ],
        ];
        $yaml = Yaml::fromArray($data);
        $expected = <<< EOT
services: 
  app: 
    build: .
    depends_on: 
      - db
  memcached: 
    image: memcached
volumes: 
  mysql: abc
EOT;
        $this->assertContains($expected, $yaml);
    }

    public function testParseValues()
    {
        $yaml = <<< EOF
enabled: true
disabled: false
empty: null
EOF;
        $result = Yaml::toArray($yaml);
        $this->assertEquals(true, $result['enabled']);
        $this->assertEquals(false, $result['disabled']);
        $this->assertNull($result['empty']);
    }

    public function testParseIndexedList()
    {
        $yaml = <<< EOT
---
# List of fruits
-
  name: james
-
  name: amy
EOT;
        $expected = [['name' => 'james'],['name' => 'amy']];
        $this->assertEquals($expected, Yaml::toArray($yaml));
    }

    public function testParseList()
    {
        $yaml = <<< EOT
---
# List of fruits
fruits:
    - Apple
    - Orange
    - Banana
EOT;
        $expected = ['fruits' => ['Apple','Orange','Banana']];
        $this->assertEquals($expected, Yaml::toArray($yaml));
    }

    public function testParseDictonary()
    {
        $yaml = <<< EOT
---
# Employee record
employee:
    name: James
    position: Senior Developer
EOT;
   
        $expected = ['employee' => ['name' => 'James','position' => 'Senior Developer']];
        $this->assertEquals($expected, Yaml::toArray($yaml));
    }

    public function testParseRecordSet()
    {
        $yaml = <<< EOT
---
# Employees 
- 100:
  name: James
  position: Senior Developer
- 200:
  name: Tony
  position: Manager

EOT;
        $expected = [
            '100' => ['name' => 'James','position' => 'Senior Developer'],
            '200' => ['name' => 'Tony','position' => 'Manager'],
        ];
        $this->assertEquals($expected, Yaml::toArray($yaml));
    }

    public function testParseMultiLineBlock()
    {
        $yaml = <<< EOT
block_1: |
            this is a multiline block
            of text
block_2: >
            this also is a multiline block
            of text
EOT;
        $expected = [
            'block_1' => "this is a multiline block\nof text", // literal
            'block_2' => 'this also is a multiline block of text', // folded
        ];
        $result = Yaml::toArray($yaml);
         
        $this->assertSame($expected, Yaml::toArray($yaml));
    }

    public function testComplicated()
    {
        $yaml = <<< EOT
---
# Employee record
name: James Anderson
job: PHP developer
active: true
fruits:
    - Apple
    - Banana
phones:
    home: 0207 123 4567
    mobile: 123 456 567
addresses:
    - street: 2 Some road
      city: London
    - street: 5 Some avenue
      city: Manchester
description: |
    Lorem ipsum dolor sit amet, 
    ea eum nihil sapientem, timeam 
    constituto id per. 
EOT;
        $expected = '{"name":"James Anderson","job":"PHP developer","active":true,"fruits":["Apple","Banana"],"phones":{"home":"0207 123 4567","mobile":"123 456 567"},"addresses":[{"street":"2 Some road","city":"London"},{"street":"5 Some avenue","city":"Manchester"}],"description":"Lorem ipsum dolor sit amet,\nea eum nihil sapientem, timeam\nconstituto id per."}';
        $this->assertEquals($expected, json_encode(Yaml::toArray($yaml)));
    }

    public function testParseChildNumericalList()
    {
        $yaml = <<< EOT
# Employee record
name: James
addresses:
    -
      city: London
    -
      city: Liverpool
EOT;
        $expected = [['city' => 'London'],['city' => 'Liverpool']];
        $result = Yaml::toArray($yaml);
                 
        $this->assertSame($expected, $result['addresses']);
    }

    public function testUsingTabsException()
    {
        $this->expectException(YamlException::class);
        $yaml = "\tname: no tab please";
        Yaml::toArray($yaml);
    }

    public function testMultiDocumentStreamException()
    {
        $this->expectException(YamlException::class);
        $yaml = "...\nname: value...";
        Yaml::toArray($yaml);
    }
}
