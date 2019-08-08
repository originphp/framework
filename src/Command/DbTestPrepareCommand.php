<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Command;

use Origin\Model\ConnectionManager;

class DbTestPrepareCommand extends Command
{
    protected $name = 'db:test:prepare';
    protected $description = 'Prepares the test database using the current schema file';
    
    public function execute()
    {
        $config = ConnectionManager::config('test');
        if (! $config) {
            $this->throwError('test datasource not found');
        }
        $connection = ConnectionManager::get('test');
        if (in_array($config['database'], $connection->databases())) {
            $this->runCommand('db:drop', ['--datasource=test']);
        }
        $this->runCommand('db:create', ['--datasource=test']);
        $this->runCommand('db:schema:load', ['--datasource=test']);
    }
}
