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
/**
 * When many of the model tests were created or worked many features were not implemented or have
 * changed. This is a new set of tests to eventually replace the other model tests hoping it will be less code
 * and can benefit from other features. Also since this now uses fixtures, each setUp data is reset. This will be
 * make it easier to track down errors.
 */
namespace Origin\Test\TestCase\Model;

use Generator;

trait GeneratorTestTrait
{

/**
     * Asserts that a generator has x items
     */
    protected function assertGeneratorCount(int $expected, Generator $results)
    {
        $actual = 0;

        foreach ($results as $result) {
            $actual ++;
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * Asserts that the results in the generator are a certain class
     */
    protected function assertGeneratorClass(string $expected, Generator $results)
    {
        foreach ($results as $result) {
            $this->assertInstanceOf($expected, $result);
        }
    }
}
