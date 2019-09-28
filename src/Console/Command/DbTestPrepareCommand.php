<?php
declare(strict_types = 1);
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

namespace Origin\Console\Command;

use Origin\Core\Config;
use Origin\Model\ConnectionManager;

class DbTestPrepareCommand extends Command
{
    protected $name = 'db:test:prepare';
    protected $description = 'Prepares the test database using the current schema file';
    
    public function initialize() : void
    {
        $this->addOption('type', [
            'description' => 'Which schema type to be loaded sql or php',
            'default' => Config::read('Schema.format'),
        ]);
    }
    public function execute() : void
    {
        $config = ConnectionManager::config('test');
        if (! $config) {
            $this->throwError('test datasource not found');
        }
        // Create tmp Connection
        $database = $config['database'];
        $config['database'] = null;
        $connection = ConnectionManager::create('tmp', $config);
  
        if (in_array($database, $connection->databases())) {
            $this->runCommand('db:drop', ['--connection=test']);
        }

        $this->runCommand('db:create', ['--connection=test']);
        $this->runCommand('db:schema:load', ['--connection' => 'test','--type' => $this->options('type')]);
    }
}
