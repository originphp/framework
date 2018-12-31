<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
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

class FixtureManager
{
    protected $loaded = [];

    public function __construct()
    {
        $this->initialize();
    }

    public function initialize()
    {
    }

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
        ModelRegistry::reset();
    }

    public function loadFixture(string $fixture)
    {
        $class = $this->resolveFixture($fixture);

        $createTable = false;
        if (empty($this->loaded[$fixture])) {
            $this->loaded[$fixture] = new $class();
            $createTable = true;
        }

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
      'datasource' => $this->loaded[$fixture]->datasource,
    ]);
    }

    public function unloadFixture(string $fixture)
    {
        if ($this->loaded[$fixture]->dropTables === true) {
            $this->loaded[$fixture]->drop();
        }
    }

    protected function resolveFixture(string $fixture)
    {
        list($plugin, $fixture) = pluginSplit($fixture);

        $namespace = '';
        if ($plugin == 'App') {
            $namespace = Configure::read('App.namespace');
        } elseif ($plugin == 'Framework') {
            $namespace = 'Origin';
        } elseif ($plugin) {
            $namespace = $plugin;
        }

        return $namespace."\\Test\\Fixture\\{$fixture}Fixture";
    }
}
