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

namespace Origin\TestSuite;

use Origin\TestSuite\FixtureManager;
/*
 *
 * @link https://phpunit.readthedocs.io/en/7.4/extending-phpunit.html
 */
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\Framework\AssertionFailedError;

class OriginTestListener implements TestListener
{
    /**
     * Undocumented variable
     *
     * @var \Origin\TestSuite\FixtureManager
     */
    public $fixtureManager = null;

    public function startTestSuite(TestSuite $suite): void
    {
        $this->fixtureManager = new FixtureManager();
    }

    public function startTest(Test $test): void
    {
        if ($test instanceof OriginTestCase) {
            $test->initialize();
            $test->startup();
            $this->fixtureManager->load($test);
        }
    }

    public function endTest(Test $test, float $time): void
    {
        if ($test instanceof OriginTestCase) {
            $test->shutdown();
            $this->fixtureManager->unload($test);
        }
    }

    public function endTestSuite(TestSuite $suite): void
    {
        unset($this->fixtureManager);
    }
    /**
     * @codeCoverageIgnore
     */
    public function addError(Test $test, \Throwable $e, float $time): void
    {
    }
    /**
     * @codeCoverageIgnore
     */
    public function addWarning(Test $test, Warning $e, float $time): void
    {
    }
    /**
     * @codeCoverageIgnore
     */
    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
    }
    /**
     * @codeCoverageIgnore
     */
    public function addIncompleteTest(Test $test, \Throwable $e, float $time): void
    {
    }
    /**
     * @codeCoverageIgnore
     */
    public function addRiskyTest(Test $test, \Throwable $e, float $time): void
    {
    }
    /**
     * @codeCoverageIgnore
     */
    public function addSkippedTest(Test $test, \Throwable $e, float $time): void
    {
    }
}
