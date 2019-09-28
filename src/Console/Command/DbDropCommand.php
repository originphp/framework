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

use Origin\Model\ConnectionManager;
use Origin\Model\Exception\DatasourceException;

class DbDropCommand extends Command
{
    protected $name = 'db:drop';

    protected $description = 'Drops the database for the datasource';

    public function initialize() : void
    {
        $this->addOption('connection', [
            'description' => 'Use a different datasource',
            'short' => 'c',
            'default' => 'default',
        ]);
    }
 
    public function execute() : void
    {
        $datasource = $this->options('connection');
        $config = ConnectionManager::config($datasource);
        if (! $config) {
            $this->throwError("{$datasource} datasource not found");
        }
     
        // Create tmp Connection
        $database = $config['database'];
        $config['database'] = null;
        $connection = ConnectionManager::create('tmp', $config);

        if (! in_array($database, $connection->databases())) {
            $this->io->status('error', sprintf('Database `%s` does not exist', $database));
            $this->abort();
        }
        try {
            $connection->execute("DROP DATABASE {$database}");
            ConnectionManager::drop('tmp');
            $this->io->status('ok', sprintf('Database `%s` dropped', $database));
        } catch (DatasourceException $ex) {
            $this->throwError('DatasourceException', $ex->getMessage());
        }
    }
}
