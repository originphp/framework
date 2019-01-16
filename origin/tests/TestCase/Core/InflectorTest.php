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

namespace Origin\Test\Core;

use Origin\Core\Inflector;

class InflectorTest extends \PHPUnit\Framework\TestCase
{
    public function testPluralize()
    {
        // Test rules
        $this->assertEquals('apples', Inflector::pluralize('apple'));
        $this->assertEquals('companies', Inflector::pluralize('company'));
        $this->assertEquals('branches', Inflector::pluralize('branch'));
        $this->assertEquals('businesses', Inflector::pluralize('business'));
        $this->assertEquals('backslashes', Inflector::pluralize('backslash'));
        $this->assertEquals('mailboxes', Inflector::pluralize('mailbox'));
        $this->assertEquals('responses', Inflector::pluralize('response'));
        $this->assertEquals('', Inflector::pluralize(''));

        // Test fit
        $this->assertEquals('statuses', Inflector::pluralize('status'));
        $this->assertEquals('employees', Inflector::pluralize('employee'));
        $this->assertEquals('processes', Inflector::pluralize('process'));
        $this->assertEquals('patches', Inflector::pluralize('patch'));
        $this->assertEquals('cases', Inflector::pluralize('case'));
    }

    public function testSingularize()
    {
        $this->assertEquals('apple', Inflector::singularize('apples'));
        $this->assertEquals('company', Inflector::singularize('companies'));
        $this->assertEquals('branch', Inflector::singularize('branches'));
        $this->assertEquals('business', Inflector::singularize('businesses'));
        $this->assertEquals('backslash', Inflector::singularize('backslashes'));
        $this->assertEquals('mailbox', Inflector::singularize('mailboxes'));
        $this->assertEquals('response', Inflector::singularize('responses'));
        $this->assertEquals('', Inflector::singularize(''));

        $this->assertEquals('status', Inflector::singularize('statuses'));
        $this->assertEquals('employee', Inflector::singularize('employees'));
        $this->assertEquals('process', Inflector::singularize('processes'));
        $this->assertEquals('patch', Inflector::singularize('patches'));
        $this->assertEquals('case', Inflector::singularize('cases'));
    }

    public function testCamelize()
    {
        $this->assertEquals('CamelCase', Inflector::camelize('camel_case'));
    }

    public function testUnderscore()
    {
        $this->assertEquals('contacts', Inflector::underscore('Contacts'));
        $this->assertEquals('leads', Inflector::underscore('leads'));
        $this->assertEquals('contact_notes', Inflector::underscore('ContactNotes'));
        $this->assertEquals('lead_notes', Inflector::underscore('leadNotes'));
    }

    public function testTabelize()
    {
        $this->assertEquals('contacts', Inflector::tableize('Contact'));
        $this->assertEquals('contact_emails', Inflector::tableize('ContactEmail'));
    }

    public function testvariable()
    {
        $this->assertEquals('camelCase', Inflector::variable('camel_case'));
    }

    /**
     * @depends testSingularize
     */
    public function testClassify()
    {
        $this->assertEquals('Contact', Inflector::classify('contacts'));
        $this->assertEquals('ContactEmail', Inflector::classify('contact_emails'));
    }

    public function testHumanize()
    {
        $this->assertEquals('Contact Manager', Inflector::humanize('contact_manager'));
    }

    public function testAddRules()
    {
        Inflector::rules('singular', ['/foo/i' => 'fooBar']);
        Inflector::rules('plural', ['/bar/i' => 'barFoo']);
        $this->assertEquals('fooBar', Inflector::singularize('foo'));
        $this->assertEquals('barFoo', Inflector::pluralize('bar'));
    }

    public function testDictonary()
    {
        Inflector::add('person', 'people');

        $this->assertEquals('person', Inflector::singularize('people'));
        $this->assertEquals('people', Inflector::pluralize('person'));

        $this->assertEquals('people', Inflector::tableize('Person'));
        $this->assertEquals('Person', Inflector::classify('People'));
    }
}
