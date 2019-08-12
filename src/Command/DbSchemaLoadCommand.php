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

use Origin\Core\Configure;
use Origin\Core\Inflector;
use Origin\Model\ConnectionManager;

class DbSchemaLoadCommand extends Command
{
    use DbSchemaTrait;
    protected $name = 'db:schema:load';
    protected $description = 'Loads the database schema from file';

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
            'default' => Configure::read('Schema.format'),
        ]);
    }
 
    public function execute()
    {
        $name = $this->arguments('name') ?? 'schema';
        $type = $this->options('type');
        $filename = $this->schemaFilename($name, $type);
        $datasource = $this->options('datasource');
     
        if (! in_array($type, ['sql','php'])) {
            $this->throwError(sprintf('The type `%s` is invalid', $type));
        }

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
       
        include_once $filename;
        $object = new $class;
        $statements = $object->createSql($connection);
        
        $count = $this->executeStatements($statements, $connection);

        $this->io->success(sprintf('Executed %d statements', $count));
    }
}
