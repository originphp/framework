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
declare(strict_types = 1);
namespace Origin\TestSuite;

/*
 *
 * @link https://phpunit.readthedocs.io/en/7.4/extending-phpunit.html
 */

use PHPUnit\Framework\Test;
use PHPUnit\Framework\Warning;
use PHPUnit\Framework\TestSuite;
use Origin\Model\ConnectionManager;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\AssertionFailedError;

class OriginTestListener implements TestListener
{
    /**
     * FixtureManager instance
     *
     * @var \Origin\TestSuite\FixtureManager
     */
    protected $fixtureManager = null;

    /**
     * @var boolean
     */
    private $enabled = true;

    /**
     * A test suite started.
     *
     * @param \PHPUnit\Framework\TestSuite $suite
     * @return void
     */
    public function startTestSuite(TestSuite $suite): void
    {
        $this->enabled = ConnectionManager::config('test') ? true : false;
        if ($this->enabled) {
            $this->fixtureManager = new FixtureManager();
        }
    }

    /**
     * A test started.
     *
     * @param PHPUnit\Framework\Test $test
     * @return void
     */
    public function startTest(Test $test): void
    {
        if ($test instanceof OriginTestCase && $this->enabled) {
            $this->fixtureManager->load($test);
        }
    }

    /**
     * A test ended.
     *
     * @param PHPUnit\Framework\Test $test
     * @param float $time
     * @return void
     */
    public function endTest(Test $test, float $time): void
    {
        if ($test instanceof OriginTestCase && $this->enabled) {
            $this->fixtureManager->unload($test);
        }
    }

    /**
     * A test suite ended.
     *
     * @param PHPUnit\Framework\TestSuite $suite
     * @return void
     */
    public function endTestSuite(TestSuite $suite): void
    {
        if ($this->enabled) {
            $this->fixtureManager->shutdown();
        }
    }
    /**
     * An error occurred.
     *
     * @codeCoverageIgnore
     */
    public function addError(Test $test, \Throwable $e, float $time): void
    {
    }
    /**
     * A warning occurred.
     *
     * @codeCoverageIgnore
     */
    public function addWarning(Test $test, Warning $e, float $time): void
    {
    }
    /**
     * A failure occurred.
     *
     * @codeCoverageIgnore
     */
    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
    }
    /**
     * Incomplete test.
     *
     * @codeCoverageIgnore
     */
    public function addIncompleteTest(Test $test, \Throwable $e, float $time): void
    {
    }
    /**
     * Risky test.
     *
     * @codeCoverageIgnore
     */
    public function addRiskyTest(Test $test, \Throwable $e, float $time): void
    {
    }
    /**
     *
     * @codeCoverageIgnore
     */
    public function addSkippedTest(Test $test, \Throwable $e, float $time): void
    {
    }
}
