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

use PHPUnit\Framework\TestCase;
use Origin\Core\Resolver;
use Origin\Model\Exception\MissingModelException;
use Origin\Model\ModelRegistry;

class OriginTestCase extends \PHPUnit\Framework\TestCase
{
    public $fixtures = [];
    
    /**
     * Creates a Mock model, and adds to Registry at the same time. It will load
     * config from registry to maintain fixture information.
     *
     * @param string $alias Name of model - Bookmark, MyPlugin.Contact
     * @param array $methods methods to mock
     * @param array $options (className,table,alias,datasource)
     * @return \Origin\Model\Model
     */
    public function getMockForModel(string $alias, array $methods = [], array $options =[])
    {
        if (empty($options['className'])) {
            $options['className'] = Resolver::className($alias, 'Model');
            if (!$options['className']) {
                throw new MissingModelException($alias);
            }
        }
    
        list($plugin, $alias) = pluginSplit($alias);
        $options += ['name' => $alias, 'alias' => $alias];
       
        $existingConfig = ModelRegistry::config($alias);
        if ($existingConfig) {
            $options +=  $existingConfig;
        }
     
        $mock = $this->getMock($options['className'], $methods, $options);

        ModelRegistry::set($alias, $mock);
        return $mock;
    }

    /**
     * Creates Mock object using the Mockbuilder
     *
     * @param string $className
     * @param array $methods
     * @param array $options
     * @return void
     */
    public function getMock(string $className, array $methods=[], array $options = null)
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

    public function setUp()
    {
        parent::setUp();
    }
    
    public function tearDown()
    {
        parent::tearDown();
        ModelRegistry::clear();
    }
}
