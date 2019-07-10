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

use Origin\Utility\Csv;
use Origin\Exception\InvalidArgumentException;
use Origin\Exception\NotFoundException;

class CsvTest extends \PHPUnit\Framework\TestCase
{
    public function testToArray()
    {
        $csv = <<<EOF
jim,jim@example.com
jon,jon@example.com
tony,tony@example.com
EOF;
        $expected = [
            ['jim','jim@example.com'],
            ['jon','jon@example.com'],
            ['tony','tony@example.com']
        ];
        $this->assertEquals($expected, Csv::toArray($csv));
    }

    public function testToArrayHeaders()
    {
        $expected = [
            ['jim','jim@example.com'],
            ['jon','jon@example.com'],
            ['tony','tony@example.com']
        ];

        $csv = <<<EOF
name,email
jim,jim@example.com
jon,jon@example.com
tony,tony@example.com
EOF;
        // test skip line
        $this->assertEquals($expected, Csv::toArray($csv, ['header'=>true]));
    }

    public function testToArrayKeys()
    {
        $csv = <<<EOF
name,email
jim,jim@example.com
jon,jon@example.com
tony,tony@example.com
EOF;
        $expected = [
            [
                'name'=>'jim',
                'email'=>'jim@example.com'
            ],
            [
                'name'=>'jon',
                'email'=>'jon@example.com'
            ],
            [
                'name'=>'tony',
                'email'=>'tony@example.com'
            ]
        ];
        $this->assertEquals($expected, Csv::toArray($csv, ['header'=>true,'keys'=>true]));
        $expected = [
            [
                'First Name'=>'jim',
                'Email Address'=>'jim@example.com'
            ],
            [
                'First Name'=>'jon',
                'Email Address'=>'jon@example.com'
            ],
            [
                'First Name'=>'tony',
                'Email Address'=>'tony@example.com'
            ]
        ];
        $this->assertEquals($expected, Csv::toArray($csv, ['header'=>true,'keys'=>['First Name','Email Address']]));
       
        $this->expectException(InvalidArgumentException::class);
        Csv::toArray($csv, ['header'=>true,'keys'=>['Foo']]);
    }

    public function testFromArray()
    {
        $data = [
            ['james','james@example.com'],
            ['tony','tony@example.com'],
            ['amanda','amanda@example.com'],
        ];
        $expected = "james,james@example.com\ntony,tony@example.com\namanda,amanda@example.com\n";

        $data = [
            ['name'=>'james','email'=>'james@example.com'],
            ['name'=>'tony','email'=>'tony@example.com'],
            ['name'=>'amanda','email'=>'amanda@example.com'],
        ];
        $expected = "name,email\njames,james@example.com\ntony,tony@example.com\namanda,amanda@example.com\n";
        $this->assertEquals($expected, Csv::fromArray($data, ['header'=>true]));

        $expected = "\"First Name\",\"Email Address\"\njames,james@example.com\ntony,tony@example.com\namanda,amanda@example.com\n";
        $this->assertEquals($expected, Csv::fromArray($data, ['header'=>['First Name','Email Address']]));
    }

    public function testProcess()
    {
        $tmp = TMP . DS . uid();
        file_put_contents($tmp, "name,email\njim,jim@example.com\njon,jon@example.com\ntony,tony@example.com");
        $expected = [
            [
                'Name'=>'jim',
                'Email'=>'jim@example.com'
            ],
            [
                'Name'=>'jon',
                'Email'=>'jon@example.com'
            ],
            [
                'Name'=>'tony',
                'Email'=>'tony@example.com'
            ]
        ];

        $result = [];
        $rows = Csv::process($tmp, ['header'=>true,'keys'=>['Name','Email']]);
        foreach ($rows as $i => $row) {
            $result[] = $row;
            $this->assertEquals($i, $rows->key());
        }
        $this->assertEquals($expected, $result);

        $expected = [
            [
                'name'=>'jim',
                'email'=>'jim@example.com'
            ],
            [
                'name'=>'jon',
                'email'=>'jon@example.com'
            ],
            [
                'name'=>'tony',
                'email'=>'tony@example.com'
            ]
        ];

        $result = [];
        $rows = Csv::process($tmp, ['header'=>true]);
        foreach ($rows as $row) {
            $result[] = $row;
        }
       
        $this->assertEquals($expected, $result);
    }

    public function testProcessException()
    {
        $this->expectException(NotFoundException::class);
        Csv::process('/somewhere/outthere');
    }

    public function testCount()
    {
        $tmp = TMP . DS . uid();
        file_put_contents($tmp, "name,email\njim,jim@example.com\njon,jon@example.com\ntony,tony@example.com");
        $rows = Csv::process($tmp, ['header'=>true]);
        $this->assertEquals(3, count($rows));
    }

    public function testInvalidAmountOfKeys()
    {
        $this->expectException(InvalidArgumentException::class);
        $tmp = TMP . DS . uid();
        file_put_contents($tmp, "name,email\njim,jim@example.com\njon,jon@example.com\ntony,tony@example.com");
        $rows = Csv::process($tmp, ['header'=>true,'keys'=>['name']]);
        $rows->current();
    }
}
