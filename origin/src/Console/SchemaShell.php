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

namespace Origin\Console;

use Origin\Model\Schema;
use Origin\Model\ConnectionManager;
use Origin\Model\QueryBuilder;

/**
 * @todo think about importing generating plugin stuff. and option parsing
 */
class SchemaShell extends Shell
{
    public function initialize()
    {
        $this->addCommand('generate', ['help'=>'Generates the config\schema\\table.php file or file']);
        $this->addCommand('create', ['help'=>'Creates the tables using the schema .php file or files']);
        $this->addCommand('import', ['help'=>'Imports raw SQL from file or files']);
        $this->addOption('datasource', ['help'=>'Use a different datasource','value'=>'name','short'=>'ds']);
        $this->loadTask('Status');
    }

    public function generate()
    {
        $datasource = 'default';
        if (!empty($this->params['datasource'])) {
            $datasource = $this->params['datasource'];
        }
       
        $schema = new Schema();
        $connection = ConnectionManager::get($datasource);
        $tables = $connection->tables();
        if (!empty($this->args)) {
            $tables = [$this->args[0]];
        }
        $folder = CONFIG . DS . 'schema';
        if (!file_exists($folder)) {
            mkdir($folder);
        }

        foreach ($tables as $table) {
            $data = $connection->schema($table);
            if (!$data) {
                $this->Status->error($table);
                continue;
            }
            
            $filename = $folder . DS . $table . '.php';
            $data = '<?php' . "\n" . '$schema = ' .var_export($data, true). ';';
            if (file_put_contents($filename, $data)) {
                $this->Status->ok(sprintf('Generated schema for %s', $table));
            } else {
                $this->Status->error(sprintf('Could not save to %s', $filename));
            }
        }
    }

    /**
     * Creates schema from PHP files
     *
     * @return void
     */
    public function create()
    {
        $datasource = 'default';
        if (!empty($this->params['datasource'])) {
            $datasource = $this->params['datasource'];
        }

        $connection = ConnectionManager::get($datasource);
        $folder = CONFIG . DS . 'schema';
        $files = scandir($folder);
        if ($this->args) {
            $files = [$this->args[0] .'.php'];
        }
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $table = pathinfo($file, PATHINFO_FILENAME);
                $sql = $connection->createTable($table, $this->loadSchema($folder . DS . $file));
                if ($sql and $connection->execute($sql)) {
                    $this->Status->ok(sprintf('%s table created', $table));
                    continue;
                }
                $this->Status->error(sprintf('Could not create %s', $table));
            }
        }
    }

    public function dump()
    {
        $this->out('Schema Dump');
    
        $datasource = 'default';
        if (!empty($this->params['datasource'])) {
            $datasource = $this->params['datasource'];
        }
        $connection = ConnectionManager::get($datasource);

        
        $dump = ["SET FOREIGN_KEY_CHECKS=0;"];
        $records = [];
        foreach ($connection->tables() as $table) {
            # Create Table
            $connection->execute("SHOW CREATE TABLE {$table}");
            $result = $connection->fetch();
            if (empty($result['Create Table'])) {
                $this->Status->error($table);
                continue;
            }
            $dump[] = $result['Create Table'] .';';

            # Dump Records
            $builder = new QueryBuilder($table);
            $connection->execute("SELECT * FROM {$table}");
            $results = $connection->fetchAll();
            foreach ($results as $record) {
                $sql = $builder->insert($record)
                                ->write();

                $values = $builder->getValues();
                foreach ($values as $key => $value) {
                    if ($value === null) {
                        $replaceWith  = 'NULL';
                    } elseif (is_integer($value) or is_double($value) or is_float($value) or is_numeric($value)) {
                        $replaceWith  = $value;
                    } else {
                        $value = addslashes($value);
                        $replaceWith  = "'{$value}'";
                    }
                    $sql = preg_replace("/\B:{$key}/", $replaceWith, $sql);
                }
              
                $records[] = $sql .';';
            }
            $this->Status->ok(sprintf('Processed %s table with %d records ', $table, count($results)));
        }
        
        $result =  file_put_contents(TMP . DS . 'dump.sql', implode("\n\n", $dump) . "\n\n" . implode("\n", $records));
        if ($result) {
            $this->Status->ok('Saved to tmp/dump.sql');
        } else {
            $this->Status->error('Could not save to tmp/dump.sql');
        }
    }
    public function import()
    {
        $datasource = 'default';
        if (!empty($this->params['datasource'])) {
            $datasource = $this->params['datasource'];
        }
        $connection = ConnectionManager::get($datasource);

        $default = 'schema';
        if ($this->args) {
            $default = $this->args[0];
        }
        $filename = CONFIG . DS .'schema'. DS . $default . '.sql';
        
        if (!file_exists($filename)) {
            $this->Status->error('config/schema/'.$default. '.sql not found');
            exit();
        }


        $sql = preg_replace('!/\*.*?\*/!s', '', file_get_contents($filename)); // Remove comments

        $array = explode(";\n", $sql);
        $this->out(sprintf('Running <white>%s</white> queries', count($array)));

        foreach ($array as $query) {
            if ($query != '' and $query != "\n") {
                $query = trim($query) . ';';
                if ($connection->execute($query)) {
                    $this->Status->ok($query);
                } else {
                    $this->Status->error($query);
                    return ;
                }
            }
        }
    }
    protected function loadSchema(string $filename)
    {
        include $filename;
        return $schema;
    }
}
