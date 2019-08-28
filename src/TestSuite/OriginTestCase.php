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

use Origin\Core\Resolver;
use Origin\Model\ModelRegistry;
use Origin\Model\Traits\ModelTrait;
use Origin\Model\Exception\MissingModelException;

class OriginTestCase extends \PHPUnit\Framework\TestCase
{
    use ModelTrait;
    /**
     * Holds the Fixtures list
     * examples
     * Article, MyPlugin.Article, Origin.Article.
     *
     * @var array
     */
    public $fixtures = [];

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->initialize();
    }

    /**
     * Intialize Hook. This is called before a test starts.
     */
    public function initialize()
    {
    }

    /**
     * This is called after initialize and after fixtures have been loaded, but before the tests starts.
     */
    public function startup()
    {
    }

    /**
     * This is called after the test has run.
     */
    public function shutdown()
    {
    }

    /**
     * Loads a fixture (must be called from Initialize).
     *
     * @param string $name Post or MyPlugin.Post
     */
    public function loadFixture(string $name)
    {
        if (! in_array($name, $this->fixtures)) {
            $this->fixtures[] = $name;
        }
    }

    /**
     * Loads multiple fixtures (only works from Initialize).
     *
     * @param array $fixtures
     */
    public function loadFixtures(array $fixtures)
    {
        foreach ($fixtures as $fixture) {
            $this->loadFixture($fixture);
        }
    }

    /**
     * Creates a Mock model, and adds to Registry at the same time. It will load
     * config from registry to maintain fixture information.
     *
     * @param string $alias   Name of model - Bookmark, MyPlugin.Contact
     * @param array  $methods methods to mock
     * @param array  $options (className,table,alias,datasource)
     *
     * @return \Origin\Model\Model
     */
    public function getMockForModel(string $alias, array $methods = [], array $options = [])
    {
        if (empty($options['className'])) {
            $options['className'] = Resolver::className($alias, 'Model');
            if (! $options['className']) {
                throw new MissingModelException($alias);
            }
        }

        list($plugin, $alias) = pluginSplit($alias);
        $options += ['name' => $alias, 'alias' => $alias];

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

    protected function setUp() : void
    {
        parent::setUp();
        $this->startup();
    }

    protected function tearDown() : void
    {
        parent::tearDown();
        $this->shutdown();
    }
}
