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
use Origin\Model\ConnectionManager;
use Origin\Core\Inflector;
use Origin\Model\Exception\DatasourceException;
trait DbSchemaTrait
{
    /**
     * This loads config/schema
     *
     * @param string $datasource
     * @param string $name schema, structure, Model.schema
     * @return void
     */
    public function loadSchema(string $name,string $datasource)
    {
        $filename = $this->schemaFilename($name);
        if(file_exists($filename)){
            return $this->runSchema(file_get_contents($filename), $datasource);
        }
        return false;
    }

    /**
     * Checks if a schema file exists
     *
     * @param string $name schema or Plugin.schema
     * @return boolean
     */
    public function schemaExists(string $name){
        
        $filename = $this->schemaFilename($name);
        return file_exists($filename);
    }   
    
    /**
     * Gets the filename for the schema
     *
     * @param string $name schema or Plugin.schema
     * @return string
     */
    public function schemaFilename(string $name,string $extension='sql') : string
    {
        list($plugin, $file) = pluginSplit($name);
        if ($plugin) {
            return PLUGINS . DS . Inflector::underscore($plugin) ."/db/{$file}.sql";
        }
        return ROOT . "/db/{$file}.{$extension}";
    }

    /**
     * Runs the contents of a sql schema file
     *
     * @param string $statement
     * @param string $datasource
     * @return void
     */
    public function runSchema(string $statement, string $datasource)
    {
        $statement= preg_replace('!/\*.*?\*/!s', '', $statement); // Remove comments
        $array = explode(";\n", $statement);
        
        if(!ConnectionManager::config($datasource)){
            $this->throwError("{$datasource} datasource not found");
        }

        $connection = ConnectionManager::get($datasource);
      
        if(empty($array)){
            $this->throwError("schema file is empty");
        }

        foreach ($array as $query) {
            if ($query != '' and $query != "\n") {
                $query = trim($query) . ';';
                
                try {
                    $connection->execute($query);
                } catch (DatasourceException $ex) {
                    $this->io->status('error',str_replace("\n",'',$query));
                    $this->throwError($ex->getMessage());
                }
                $this->io->status('ok',str_replace("\n",'',$query));
            }
        }
        return true;
    }
}