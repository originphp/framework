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

use Origin\Core\Inflector;
use Origin\Model\ConnectionManager;
use Origin\Model\Exception\DatasourceException;

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
            'default' => 'default',
        ]);
        $this->addArgument('name', [
            'description' => 'schema_name or Plugin.schema_name',
        ]);
        $this->addOption('type', [
            'description' => 'How the schema will be dumped, in sql or php',
            'default' => 'sql',
        ]);
    }
 
    public function execute()
    {
        $name = $this->arguments('name') ?? 'schema';
        $datasource = $this->options('datasource');
        $type = $this->options('type');
        $filename = $this->schemaFilename($name, $type);
        if ($type === 'php') {
            $this->loadPhpSchema($name, $filename, $datasource);
        } else {
            $this->loadSchema($filename, $datasource);
        }
    }

    public function loadPhpSchema(string $name, string $filename, string $datasource) : void
    {
        if (! file_exists($filename)) {
            $this->throwError("File {$filename} not found");
        }
        $this->io->info("Loading {$filename}");
       
        if (! ConnectionManager::config($datasource)) {
            $this->throwError("{$datasource} datasource not found");
        }
        $connection = ConnectionManager::get($datasource);

        list($plugin, $name) = pluginSplit($name);
        $class = 'ApplicationSchema';
        if ($name !== 'schema') {
            $class = Inflector::camelize($name) . 'Schema';
        }
       
        include $filename;
        $object = new $class;
        $statements = $object->createSql($connection);

        $connection->disableForeignKeyConstraints();
     
        foreach ($statements  as $statement) {
            try {
                $connection->execute($statement);
            } catch (DatasourceException $ex) {
                $this->io->status('error', str_replace("\n", '', $statement));
                $this->throwError('Executing query failed', $ex->getMessage());
            }
            $this->io->status('ok', str_replace("\n", '', $statement));
        }
        $connection->commit();
        $connection->enableForeignKeyConstraints();
       
        $this->io->success(sprintf('Executed %d statements', count($statements)));
    }
}
