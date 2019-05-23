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
use Origin\Model\ModelRegistry;
use Origin\Model\ConnectionManager;

class FixtureManager
{
    protected $loaded = [];

    /**
     * Loads fixtures defined in a test.
     *
     * @param PHPUnit\Framework\Test $test
     */
    public function load($test)
    {
        foreach ($test->fixtures as $fixture) {
            $this->loadFixture($fixture);
        }
    }

    /**
     * Unloads fixtures defined in a test.
     *
     * @param PHPUnit\Framework\Test $test
     */
    public function unload($test)
    {
        foreach ($test->fixtures as $fixture) {
            $this->unloadFixture($fixture);
        }
    
        // Clear the model registry
        ModelRegistry::clear();
    }

    public function loaded(string $fixture = null)
    {
        if ($fixture === null) {
            return $this->loaded;
        }
        return isset($this->loaded[$fixture]);
    }

    public function loadFixture(string $fixture)
    {
        $class = $this->resolveFixture($fixture);

        $createTable = false;
        if (empty($this->loaded[$fixture])) {
            $this->loaded[$fixture] = new $class();
            $createTable = true;
        }

       $this->disableForeignKeyConstraints($this->loaded[$fixture]->datasource);

        if ($createTable or $this->loaded[$fixture]->dropTables === true) {
            /* @todo waiting for sql schema to be migrated which also invovles rewriting tests to changes, once done this can be removed because unload fixture drops table. */
            $this->loaded[$fixture]->drop();
            $this->loaded[$fixture]->create();
        } else {
            $this->loaded[$fixture]->truncate();
        }

        $this->loaded[$fixture]->initialize();
        $this->loaded[$fixture]->insert();
      
        // Config Model in Registry to use test datasource for this fixture
        list($plugin, $alias) = pluginSplit($fixture);
    
        ModelRegistry::config($alias, [
            'datasource' => $this->loaded[$fixture]->datasource
        ]);
  
        $this->enableForeignKeyConstraints($this->loaded[$fixture]->datasource);
    }

    public function unloadFixture(string $fixture)
    {
        if(empty($this->loaded[$fixture])){
            return;
        }
        $this->disableForeignKeyConstraints($this->loaded[$fixture]->datasource);
        
        $this->loaded[$fixture]->truncate();
        
        $this->enableForeignKeyConstraints($this->loaded[$fixture]->datasource);
    }


    /**
     * End test shutdown process
     *
     * @return void
     */
    public function shutdown(){
        foreach($this->loaded as $fixture){
            $this->disableForeignKeyConstraints($fixture->datasource);
            $fixture->drop();
            $this->enableForeignKeyConstraints($fixture->datasource);
        }
    }

    protected function disableForeignKeyConstraints(string $datasource)
    {
        ConnectionManager::get($datasource)->disableForeignKeyConstraints();
    }

    protected function enableForeignKeyConstraints(string $datasource)
    {
        ConnectionManager::get($datasource)->enableForeignKeyConstraints();        
    }

    protected function resolveFixture(string $fixture)
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
