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

use Origin\Model\Entity;
use Origin\Core\Resolver;
use Origin\Mailer\Mailer;
use Origin\Core\HookTrait;
use Origin\Core\ModelTrait;
use Origin\Model\ModelRegistry;
use Origin\Model\Exception\MissingModelException;

abstract class OriginTestCase extends \PHPUnit\Framework\TestCase
{
    use ModelTrait, HookTrait;
    /**
     * Holds the Fixtures list
     * examples
     * Article, MyPlugin.Article, Origin.Article.
     *
     * @var array
     */
    protected $fixtures = [];

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->executeHook('initialize');
    }

    /**
     * Creates a Mock model, and adds to Registry at the same time. It will load
     * config from registry to maintain fixture information.
     *
     * @param string $alias   Name of model - Bookmark, MyPlugin.Contact
     * @param array  $methods methods to mock
     * @param array  $options (className,table,alias,datasource)
     *
     * @return \Origin\Model\Model|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getMockForModel(string $alias, array $methods = [], array $options = [])
    {
        if (empty($options['className'])) {
            $options['className'] = Resolver::className($alias, 'Model');
            if (! $options['className']) {
                throw new MissingModelException($alias);
            }
        }

        list($namespace, $modelName) = namespaceSplit($options['className']);
        $entityClass = $namespace . '\Entity\\' . $modelName;
        $entityClass = class_exists($entityClass) ? $entityClass : Entity::class;

        list($plugin, $alias) = pluginSplit($alias);
        $options += [
            'name' => $alias,
            'alias' => $alias,
            'entityClass' => $entityClass
        ];
        
        $existingConfig = ModelRegistry::config($alias);
        if ($existingConfig) {
            $options += $existingConfig;
        }

        $mock = $this->getMock($options['className'], $methods, $options);

        ModelRegistry::set($alias, $mock);

        return $mock;
    }

    /**
     * Creates Mock object using the Mockbuilder.
     *
     * @param string $className
     * @param array  $methods
     * @param array  $options
     */
    public function getMock(string $className, array $methods = [], array $options = null)
    {
        if ($options) {
            return $this->getMockBuilder($className)
                ->setMethods($methods)
                ->setConstructorArgs([$options])
                ->getMock();
        }

        return $this->getMockBuilder($className)
            ->setMethods($methods)
            ->getMock();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->executeHook('startup');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->executeHook('shutdown');
        if (method_exists($this, 'cleanup')) {
            $this->cleanup();
        }
        if (class_exists(Mailer::class)) {
            Mailer::clearDelivered();
        }
    }

    /**
     * Getter and setter for fixtures
     *
     * @param array $fixtures
     * @return array
     */
    public function fixtures(array $fixtures = null): array
    {
        if ($fixtures === null) {
            return $this->fixtures;
        }

        return $this->fixtures = $fixtures;
    }

    /**
    * Executes code that has been deprecated without trigering the
    * deprecation warning.
    *
    * @example
    *
    *  $this->deprecated(function () {
    *      $object = new CustoObject();
    *      $this->assertTrue($object->dontUse());
    *  });
    */
    public function deprecated(callable $callable)
    {
        $level = error_reporting();
        error_reporting(E_ALL & ~E_USER_DEPRECATED);
        $callable();
        error_reporting($level);
    }

    /**
     * A less ugly short wrapper for assertStringContainsString
     *
     * @param string $needle
     * @param string $haystack
     * @param string $message
     * @return void
     */
    public function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertStringContainsString($needle, $haystack, $message);
    }

    /**
     * A less ugly short wrapper for assertStringNotContainsString
     *
     * @param string $needle
     * @param string $haystack
     * @param string $message
     * @return void
     */
    public function assertStringNotContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertStringNotContainsString($needle, $haystack, $message);
    }
}
