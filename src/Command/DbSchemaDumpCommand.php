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

class DbSchemaDumpCommand extends Command
{
    use DbSchemaTrait;
    
    protected $name = 'db:schema:dump';
    protected $description = 'Dumps the schema to a sql file';

    protected $template =
    '<?php
use Origin\Model\Schema;

class %name%Schema extends Schema
{
    const VERSION = %version%;

%define%
}
';

    public function initialize()
    {
        $this->addOption('datasource', [
            'description' => 'Use a different datasource',
            'short' => 'ds',
            'default' => 'default',
        ]);
        $this->addOption('type', [
            'description' => 'How the schema will be dumped, in sql or php',
            'default' => 'sql',
        ]);
        $this->addArgument('name', [
            'description' => 'schema_name or Plugin.schema_name',
        ]);
    }
 
    public function execute()
    {
        $name = $this->arguments('name') ?? 'schema';

        $datasource = $this->options('datasource');
        if (! ConnectionManager::config($datasource)) {
            $this->throwError("{$datasource} datasource not found");
        }

        $type = $this->options('type');
        if (! in_array($type, ['sql','php'])) {
            $this->throwError(sprintf('The type `%s` is invalid', $type));
        }

        $filename = $this->schemaFilename($name, $type);
        $this->io->info("Dumping schema to {$filename}");
        if ($type === 'sql') {
            $this->dump($datasource, $name);
        } else {
            $this->dumpPhp($datasource, $name);
        }
    }

    protected function dumpPhp(string $datasource, string $name)
    {
        $filename = $this->schemaFilename($name, 'php');
        list($plugin, $name) = pluginSplit($name);
        $className = 'Application';
        if ($name !== 'schema') {
            $className = Inflector::camelize($name);
        }
       
        $connection = ConnectionManager::get($datasource);
        $out = [];
        $tables = $connection->tables();
        foreach ($tables as $table) {
            $data = $connection->adapter()->describe($table);
            $this->io->list($table);
            $columns = [];
            $columns[] = $this->datasetToString('columns', $data['columns']);
            $columns[] = $this->datasetToString('constraints', $data['constraints']);
            $columns[] = $this->datasetToString('indexes', $data['indexes']);

            if (isset($data['options'])) {
                $options = $this->values($data['options']);
                $columns[] = "\t\t'options' => " . '[' . implode(', ', $options) . ']';
            }

            $out[] = "\tpublic \${$table} = [\n" . implode(",\n", $columns) .  "\n\t];\n" ;
        }
        $template = str_replace('%version%', date('Ymdhis'), $this->template);
        $template = str_replace('%define%', implode("\n", $out), $template);
        $template = str_replace('%name%', $className, $template);
    
        if (! $this->io->createFile($filename, $template)) {
            $this->throwError('Error saving schema file');
        }
    }

    protected function datasetToString(string $key, array $data)
    {
        $out = '[]';
        if ($data) {
            $out = [];
            foreach ($data as $name => $definition) {
                $column = $this->values($definition);
                $out[] = "\t\t'{$name}' => " . '[' . implode(', ', $column) . ']';
            }
            $out = "[\n\t" . implode(",\n\t", $out) . "\n\t\t]";
        }

        return "\t\t'{$key}' => " . $out;
    }

    protected function values(array $data) : array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $out[] = "'{$key}' => [" . implode(', ', $this->values($value)) . ']';
            } else {
                $value = var_export($value, true);
                if (is_string($key)) {
                    $out[] = "'{$key}' => {$value}";
                } else {
                    $out[] = $value;
                }
            }
        }

        return $out;
    }

    protected function varExport(array $data)
    {
        $data = var_export($data, true);
        $data = str_replace(
            ['array (', "),\n", " => \n"],
            ['[', "],\n", ' => '],
            $data
        );
        $data = preg_replace('/=>\s\s+\[/i', '=> [', $data);
        $data = preg_replace("/=> \[\s\s+\]/m", '=> []', $data);

        return substr($data, 0, -1).']';
    }

    protected function dump(string $datasource, string $name)
    {
        $connection = ConnectionManager::get($datasource);
        $dump = [];
        $filename = $this->schemaFilename($name, 'sql');
     
        /**
         * I would like to use pg_dump, however I started getting version matching errors so
         * therefore I am not sure this is going to be good
         * @example shell_exec("pg_dump -h {$config['host']} -s {$config['database']} -U {$config['username']}");
         */
        //
        foreach ($connection->tables() as $table) {
            $dump[] = $connection->adapter()->showCreateTable($table) . ';';
            $this->io->list($table);
        }

        if (! $this->io->createFile($filename, implode("\n\n", $dump))) {
            $this->throwError('Error saving schema file');
        }
    }
}
