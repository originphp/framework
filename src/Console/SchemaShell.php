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

use Origin\Model\ConnectionManager;
use Origin\Model\QueryBuilder;
use Origin\Core\Logger;

/**
 * @todo think about importing generating plugin stuff. and option parsing
 */
class SchemaShell extends Shell
{
    public function initialize()
    {
        $this->addCommand('generate', ['help'=>'Generates the schema file or files.']);
        $this->addCommand('create', ['help'=>'Creates the tables using the schema file or files']);
        $this->addCommand('import', ['help'=>'Imports raw SQL from file or files']);
        $this->addOption('datasource', ['help'=>'Use a different datasource','value'=>'name','short'=>'ds']);
    }

    public function generate()
    {
        $datasource = 'default';
        if (!empty($this->params('datasource'))) {
            $datasource = $this->params('datasource');
        }
  
        $connection = ConnectionManager::get($datasource);
        $tables = $connection->tables();
        if ($this->args()) {
            $tables = $this->args();
        }
        $folder = CONFIG . DS . 'schema';
        if (!file_exists($folder)) {
            mkdir($folder);
        }

        foreach ($tables as $table) {
            $data = $connection->schema($table);
            if (!$data) {
                $this->status('error', $table);
                continue;
            }
            
            $filename = $folder . DS . $table . '.php';
            $data = '<?php' . "\n" . '$schema = ' .var_export($data, true). ';';
            if (file_put_contents($filename, $data)) {
                $this->status('ok', sprintf('Generated schema for %s', $table));
            } else {
                $this->error(sprintf('Could not save to %s', $filename));
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
        if (!empty($this->params('datasource'))) {
            $datasource = $this->params('datasource');
        }

        $connection = ConnectionManager::get($datasource);
        $folder = CONFIG . DS . 'db';
        $files = scandir($folder);
        if ($this->args()) {
            $files = [$this->args(0) .'.php'];
        }
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $table = pathinfo($file, PATHINFO_FILENAME);
                $sql = $connection->createTable($table, $this->loadSchema($folder . DS . $file));
                if ($sql and $connection->execute($sql)) {
                    $this->status('ok', sprintf('%s table created', $table));
                    continue;
                }
                $this->status('error', sprintf('Could not create %s', $table));
            }
        }
    }
    
    public function import()
    {
        $datasource = 'default';
        if (!empty($this->params('datasource'))) {
            $datasource = $this->params('datasource');
        }

        $connection = ConnectionManager::get($datasource);

        $default = 'schema';
        if ($this->args()) {
            $default = $this->args(0);
        }
        $filename = CONFIG . DS .'db'. DS . $default . '.sql';
        
        if (!file_exists($filename)) {
            $this->error('config/schema/'.$default. '.sql not found');
        }

        $sql = preg_replace('!/\*.*?\*/!s', '', file_get_contents($filename)); // Remove comments

        $array = explode(";\n", $sql);
        $this->out(sprintf('Running <white>%s</white> queries', count($array)));

        foreach ($array as $query) {
            if ($query != '' and $query != "\n") {
                $query = trim($query) . ';';
                if ($connection->execute($query)) {
                    $this->status('ok', $query);
                } else {
                    $this->status('error', $query);
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
