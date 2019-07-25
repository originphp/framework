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
use PHPUnit\Framework\Test;
use Origin\Model\ModelRegistry;
use Origin\Model\ConnectionManager;

class FixtureManager
{
    protected $loaded = [];

    /**
     * Loads fixtures defined in a test.
     * @internal so this can be tested not setting a type
     * @param PHPUnit\Framework\Test $test
     * @return void
     */
    public function load($test) : void
    {
        foreach ($test->fixtures as $fixture) {
            $this->loadFixture($fixture);
        }
    }

    /**
     * Unloads fixtures defined in a test.
     * @internal so this can be tested not setting a type
     * @param PHPUnit\Framework\Test $test
     * @return void
     */
    public function unload($test) :void
    {
        foreach ($test->fixtures as $fixture) {
            $this->unloadFixture($fixture);
        }
    
        // Clear the model registry
        ModelRegistry::clear();
    }

    /**
     * Gets the load fixtures or fixture
     *
     * @param string $fixture
     * @return \Origin\TestSuite\Fixture|array
     */
    public function loaded(string $fixture = null)
    {
        if ($fixture === null) {
            return $this->loaded;
        }

        return isset($this->loaded[$fixture]);
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

        $this->disableForeignKeyConstraints($this->loaded[$fixture]->datasource);

        // create the table table or truncate existing
        if (! $this->loaded[$fixture]->insertOnly and ($createTable or $this->loaded[$fixture]->dropTables === true)) {
            $this->loaded[$fixture]->drop();
            $this->loaded[$fixture]->create();
        } else {
            $this->loaded[$fixture]->truncate();
        }
       
        // Insert the records
        $this->loaded[$fixture]->insert();

        $this->enableForeignKeyConstraints($this->loaded[$fixture]->datasource);

        // Configure the Model in Registry to use test datasource for this fixture
        list($plugin, $alias) = pluginSplit($fixture);
        ModelRegistry::config($alias, [
            'datasource' => $this->loaded[$fixture]->datasource,
        ]);
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
            $this->disableForeignKeyConstraints($this->loaded[$fixture]->datasource);
            $this->loaded[$fixture]->truncate();
            $this->enableForeignKeyConstraints($this->loaded[$fixture]->datasource);
        }
    }

    /**
     * End test shutdown process
     *
     * @return void
     */
    public function shutdown() : void
    {
        foreach ($this->loaded as $fixture) {
            $this->disableForeignKeyConstraints($fixture->datasource);
            if (! $fixture->insertOnly) {
                $fixture->drop();
            }
            $this->enableForeignKeyConstraints($fixture->datasource);
        }
    }

    /**
     * Disables ForeignKeyConstrains for a datasource
     *
     * @param string $datasource
     * @return void
     */
    protected function disableForeignKeyConstraints(string $datasource) : void
    {
        ConnectionManager::get($datasource)->disableForeignKeyConstraints();
    }

    /**
    * Enables ForeignKeyConstrains for a datasource
    *
    * @param string $datasource
    * @return void
    */
    protected function enableForeignKeyConstraints(string $datasource) : void
    {
        ConnectionManager::get($datasource)->enableForeignKeyConstraints();
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
