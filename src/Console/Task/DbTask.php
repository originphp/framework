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
    public function dump($datasource='default',string $name = 'schema')
    {
        $connection = ConnectionManager::get($datasource);
        $dump = [];
        if($connection->engine()==='mysql'){
            $dump[] = "SET FOREIGN_KEY_CHECKS=0;";
        }
        $filename = ROOT . DS . 'db' .DS . $name . '.sql';
       
        if(file_exists($filename)){
            $input = $this->shell()->in('Schema file already exists, do you want to continue?',['y','n'],'n');
            if($input === 'n'){
                return false;
            }
        }
        foreach ($connection->tables() as $table) {
            $sql = $connection->adapter()->showCreateTable($table);
            if($sql === null){
                $this->shell()->error("Error dumping {$table}");
            }

            $dump[] = $sql  .';';
        }
        if (!file_put_contents($filename, implode("\n\n", $dump))) {
            $this->error('Error saving schema.sql');
        }
        return true;
    }

    public function generate(string $datasource='default',string $name = 'schema'){

        $connection = ConnectionManager::get($datasource);
    
        $tables = $connection->tables();
        $folder = ROOT . DS . 'db' .DS . $name;
        if (!file_exists($folder)) {
            mkdir($folder);
        }
        foreach ($tables as $table) {
            $data = $connection->schema($table);
            if (!$data) {
                $this->status('error', $table);
                continue;
            }
            
        
            $schema = var_export($data, true);
         
            $schema = str_replace(
                ['array (','),'," => \n",'=>   ['],
                ['[','],'," => ",'=> ['],$schema);
        
            $schema = substr($schema,0,-1) . ']';
            $data = '<?php' . "\n" . '$schema = ' . $schema . ';';

            $filename = $folder . DS . $table . '.php';
      
            if (file_put_contents($filename,    $data )) {
                $this->shell()->status('ok', sprintf('Generated schema for %s', $table));
            } else {
                $this->shell()->error(sprintf('Could not save to %s', $filename));
            }

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
    public function load(string $datasource='default', $name='schema')
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
