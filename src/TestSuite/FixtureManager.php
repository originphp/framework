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

use Origin\Core\Configure;

use Origin\Exception\Exception;
use Origin\Model\ModelRegistry;
use Origin\Model\ConnectionManager;
use Origin\Model\Exception\DatasourceException;

class FixtureManager
{
    /**
     * Loaded fixtures for this test case
     *
     * @var array
     */
    protected $loaded = [];

    /**
     * The name the of the test case which is loaded
     *
     * @var string
     */
    protected $testCaseName = null;

    /**
     * Loads fixtures defined in a test.
     * @internal so this can be tested not setting a type
     * @param \PHPUnit\Framework\Test $test
     * @return void
     */
    public function load($test) : void
    {
        $this->testCaseName = get_class($test);

        if ($test->fixtures) {
            $this->before();
            foreach ($test->fixtures as $fixture) {
                $this->loadFixture($fixture);
            }
            $this->after();
        }
    }

    /**
     * Unloads fixtures defined in a test.
     * @internal so this can be tested not setting a type
     * @param \PHPUnit\Framework\Test $test
     * @return void
     */
    public function unload($test) :void
    {
        if ($test->fixtures) {
            $this->before();
            foreach ($test->fixtures as $fixture) {
                $this->unloadFixture($fixture);
            }
            $this->after();
        }
       
        // Clear the model registry
        ModelRegistry::clear();
    }

    /**
     * Called before load or unload
     *
     * @return void
     */
    protected function before() : void
    {
        $connection = ConnectionManager::get('test');
        $connection->begin();
        $connection->disableForeignKeyConstraints();
    }

    /**
    * Called after load or unload
    *
    * @return void
    */
    protected function after() : void
    {
        $connection = ConnectionManager::get('test');
        $connection->enableForeignKeyConstraints();
        $connection->commit();
    }

    /**
     * Gets the load fixtures or fixture
     *
     * @param string $fixture
     * @return \Origin\TestSuite\Fixture|array|null
     */
    public function loaded(string $fixture = null)
    {
        if ($fixture === null) {
            return $this->loaded;
        }

        return isset($this->loaded[$fixture])?$this->loaded[$fixture]:null;
    }

    /**
     * Loads fixture
     *
     * @param string $fixture
     * @return void
     */
    public function loadFixture(string $fixture) : void
    {
        $class = $this->resolveFixture($fixture);

        $createTable = false;
        if (empty($this->loaded[$fixture])) {
            $this->loaded[$fixture] = new $class();
            $createTable = true;
        }

        try {
            // create the table table or truncate existing
            if (! $this->loaded[$fixture]->insertOnly() and ($createTable or $this->loaded[$fixture]->dropTables === true)) {
                $this->loaded[$fixture]->drop();
                $this->loaded[$fixture]->create();
            } else {
                $this->loaded[$fixture]->truncate();
            }
        } catch (DataSourceException $e) {
            ConnectionManager::get('test')->rollback(); # Cancel Transaction
            throw new Exception(sprintf('Error creating fixture %s for test case %s ', $fixture, $this->testCaseName));
        }

        try {
            // Insert the records
            $this->loaded[$fixture]->insert();
        } catch (DataSourceException $e) {
            ConnectionManager::get('test')->rollback();  # Cancel Transaction
            throw new Exception(sprintf('Error inserting records in fixture %s for test case %s ', $fixture, $this->testCaseName));
        }
    }

    /**
     * Unloads a fixture
     *
     * @param string $fixture
     * @return void
     */
    public function unloadFixture(string $fixture) : void
    {
        if (isset($this->loaded[$fixture])) {
            $this->loaded[$fixture]->truncate();
        }
    }

    /**
     * End test shutdown process
     *
     * @return void
     */
    public function shutdown() : void
    {
        $this->before();
        foreach ($this->loaded as $fixture) {
            if (! $fixture->insertOnly()) {
                $fixture->drop();
            }
        }
        $this->after();
    }

    /**
     * Resolves the class name with namespace for a fixture
     *
     * @param string $fixture
     * @return string
     */
    protected function resolveFixture(string $fixture) : string
    {
        list($plugin, $fixture) = pluginSplit($fixture);

        $namespace = '';
        if ($plugin === 'App' or $plugin === null) {
            $namespace = Configure::read('App.namespace');
        } elseif ($plugin == 'Framework') {
            $namespace = 'Origin';
        } elseif ($plugin) {
            $namespace = $plugin;
        }

        return $namespace."\\Test\\Fixture\\{$fixture}Fixture";
    }
}
