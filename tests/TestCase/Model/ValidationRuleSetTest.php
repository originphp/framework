<?php

/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Model;

use InvalidArgumentException;
use Origin\Model\ValidationRuleSet;
use Origin\TestSuite\OriginTestCase;

class ValidationRuleSetTest extends OriginTestCase
{
    public function testString()
    {
        $rule = new ValidationRuleSet('required');

        $expected = [
            'rule' => 'required',
            'message' => 'This field is required',
            'on' => null,
            'present' => false,
            'allowEmpty' => false,
            'stopOnFail' => false,
        ];
        $this->assertEquals(['rule-1' => $expected], $rule->toArray());

        $rule = new ValidationRuleSet('url');
    }

    public function testSingleRule()
    {
        $rule = new ValidationRuleSet([
            'rule' => 'other'
        ]);

        $expected = [
            'rule' => 'other',
            'message' => 'Invalid value',
            'on' => null,
            'present' => false,
            'allowEmpty' => false,
            'stopOnFail' => false,
        ];
        $this->assertEquals(['rule-1' => $expected], $rule->toArray());
    }

    public function testRuleArrayMessage()
    {
        $rule = new ValidationRuleSet([
            'rule' => ['minLength', 10],
            'message' => 'To short'
        ]);

        $expected = [
            'rule' => ['minLength', 10],
            'message' => 'To short',
            'on' => null,
            'present' => false,
            'allowEmpty' => false,
            'stopOnFail' => false,
        ];
        $this->assertEquals(['rule-1' => $expected], $rule->toArray());
    }

    public function testRuleArrayError()
    {
        $this->expectException(InvalidArgumentException::class);
        new ValidationRuleSet([
            'rule' => [123]
        ]);
    }

    public function testMultipleRules()
    {
        /**
         * Mutliple rules with INT keys
         */
        $rule = new ValidationRuleSet([
            'required', ['minLength', 10]
        ]);

        $out = $rule->toArray();

        $this->assertEquals('required', $out['rule-1']['rule']);
        $this->assertEquals(['minLength', 10], $out['rule-2']['rule']);

        /**
         * Mutliple rules with STRING keys (prefered approach)
         */
        $rule = new ValidationRuleSet([
            'required' => [
                'rule' => 'required'
            ],
            'length' => [
                'rule' => ['minLength', 10]
            ],
        ]);

        $out = $rule->toArray();
        $this->assertEquals('required', $out['rule-1']['rule']);
        $this->assertEquals(['minLength', 10], $out['rule-2']['rule']);
    }

    public function testStringParsing()
    {
        $rule = new ValidationRuleSet([
            'unique:email', 'minLength:5', 'range:10,20', 'in:a,b,c', 'notIn:1,2,3'
        ]);
        $out = $rule->toArray();
     
        $this->assertSame(['unique', 'email'], $out['rule-1']['rule']);
        $this->assertSame(['minLength', 5], $out['rule-2']['rule']);
        $this->assertSame(['range', 10, 20], $out['rule-3']['rule']);
        $this->assertSame(['in', ['a', 'b', 'c']], $out['rule-4']['rule']);
        $this->assertSame(['notIn', [1, 2, 3]], $out['rule-5']['rule']);
    }
}
