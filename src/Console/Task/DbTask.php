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

 /**
  * This will be depreciated. This is more helper as it is for output.
  */
namespace Origin\Console\Task;

use Origin\Model\ConnectionManager;
use Origin\Core\Inflector;
use Origin\Model\Exception\DatasourceException;

class DbTask extends Task
{
    public function create($datasource='default')
    {
        $config = ConnectionManager::config($datasource);
        if(!$config){
            $this->shell()->error("{$datasource} datasource not found");
        }
        $database = $config['database'];
        $config['database'] = null;
        $connection = ConnectionManager::create('tmp', $config); // add without database so we can connect
        try {
            $connection->execute("CREATE DATABASE {$database}");
            // Bug Cannot execute queries while other unbuffered queries are active , despite no results can be fetched.
            ConnectionManager::drop('tmp');
            return true;
        } catch (DatasourceException $ex) {
            $this->shell()->error($ex->getMessage());
        }
        return false;
    }

    public function drop($datasource='default')
    {
        $config = ConnectionManager::config($datasource);
        if(!$config){
            $this->shell()->error("{$datasource} datasource not found");
        }
        $database = $config['database'];
        $config['database'] = null;
        $connection = ConnectionManager::create('tmp', $config); //
        try {
            $connection->execute("DROP DATABASE {$database};");
            ConnectionManager::drop('tmp');
            return true;
        } catch (DatasourceException $ex) {
            $this->shell()->error($ex->getMessage());
        }
        return false;
    }
    /**
     * Dumps a schema file
     *
     * @return void
     */
    public function dump($datasource='default')
    {
        $connection = ConnectionManager::get($datasource);
        $dump = ["SET FOREIGN_KEY_CHECKS=0;"];
        foreach ($connection->tables() as $table) {
            # Create Table
            $connection->execute("SHOW CREATE TABLE {$table}");
            $result = $connection->fetch();
            if (empty($result['Create Table'])) {
                $this->error("Error dumping {$table}");
            }
            $dump[] = $result['Create Table'] .';';
        }
        if (!file_put_contents(ROOT .'/db/schema.sql', implode("\n\n", $dump))) {
            $this->error('Error saving schema.sql');
        }
        return true;
    }

    /**
     * This loads config/schema
     *
     * @param string $datasource
     * @param string $name schema, structure, Model.schema
     * @return void
     */
    public function load($datasource='default', $name='schema')
    {
        $filename = $this->getSQLFile($name);
        if(file_exists($filename)){
            return $this->runSQL(file_get_contents($filename), $datasource);
        }
        return false;
    }

    public function hasSQLFile($name='schema'){
        
        $filename = $this->getSQLFile($name);
        return file_exists($filename);
    }

    private function getSQLFile($name='schema') : string
    {
        list($plugin, $file) = pluginSplit($name);
        if ($plugin) {
            return PLUGINS . DS . Inflector::underscore($plugin) ."/db/{$file}.sql";
        }
        return ROOT . "/db/{$file}.sql";
    }

    private function runSQL($statement, $datasource='default')
    {
        $statement= preg_replace('!/\*.*?\*/!s', '', $statement); // Remove comments
        $array = explode(";\n", $statement);
        
        $connection = ConnectionManager::get($datasource);
        foreach ($array as $query) {
            if ($query != '' and $query != "\n") {
                $query = trim($query) . ';';
                
                try {
                    if (!$connection->execute($query)) {
                        $this->shell()->error('Error running query', $query);
                    }
                } catch (DatasourceException $ex) {
                    $this->shell()->error($ex->getMessage(), $query);
                }
            }
        }
        return true;
    }
}
