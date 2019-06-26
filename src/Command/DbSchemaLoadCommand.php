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

use Origin\Command\Command;
use Origin\Model\ConnectionManager;
use Origin\Model\Exception\DatasourceException;
use Origin\Command\DbSchemaTrait;

class DbSchemaLoadCommand extends Command
{
    use DbSchemaTrait;
    protected $name = 'db:schema:load';
    protected $description = 'Loads the schema from a sql file';

    public function initialize()
    {
        $this->addOption('datasource', [
            'description' => 'Use a different datasource',
            'short' => 'ds',
            'default' => 'default'
            ]);
        $this->addArgument('name', [
            'description' => 'schema_name or Plugin.schema_name',
            'default' => 'schema'
        ]);
    }
 
    public function execute()
    {
        $name = $this->arguments('name');
        if ($name === null) {
            $name = 'schema';
        }
        $datasource = $this->options('datasource');
        $filename = $this->schemaFilename($name);
        $this->loadSchema($filename, $datasource);
    }
}
