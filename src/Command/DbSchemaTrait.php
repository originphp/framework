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
     * Gets the filename for the schema
     *
     * @param string $name schema or Plugin.schema
     * @return string
     */
    public function schemaFilename(string $name, string $extension='sql') : string
    {
        list($plugin, $file) = pluginSplit($name);
        if ($plugin) {
            return PLUGINS . DS . Inflector::underscore($plugin) . DS . 'db' . DS .  $file . '.' . $extension;
        }
        return APP . DS . 'db' . DS . $file . '.' . $extension;
    }


    /**
     * Parses a SQL string into an array of statements
     *
     * @param string $sql
     * @return array
     */
    public function parseSql(string $sql)
    {
        # Clean Up Soure Code
         $sql = str_replace(";\r\n", ";\n", $sql); // Convert windows line endings on STATEMENTS ONLY
         $sql   = preg_replace('!/\*.*?\*/!s', '', $sql);
        $sql  = preg_replace('/^-- .*$/m', '', $sql); // Remove Comment line starting with --
         $sql  = preg_replace('/^#.*$/m', '', $sql); // Remove Comments start with #
  
         $statements = [];
        if ($sql) {
            $statements = explode(";\n", $sql);
        }
      
        return $statements;
    }

    /**
     * Runs the contents of a sql schema file
     *
     * @param string $statement
     * @param string $datasource
     * @return void
     */
    public function loadSchema(string $filename, string $datasource)
    {
        if (!file_exists($filename)) {
            $this->throwError("File {$filename} not found");
        }
        $this->io->info("Loading {$filename}");
       
        if (!ConnectionManager::config($datasource)) {
            $this->throwError("{$datasource} datasource not found");
        }
        $connection = ConnectionManager::get($datasource);
        
        $statement = file_get_contents($filename);
        $statements = $this->parseSql($statement);
        foreach ($statements  as $query) {
            $query = trim($query);
            if ($query) {
                try {
                    $connection->execute($query);
                } catch (DatasourceException $ex) {
                    $this->io->status('error', str_replace("\n", '', $query));
                    $this->throwError('Executing query failed', $ex->getMessage());
                }
                $this->io->status('ok', str_replace("\n", '', $query));
            }
        }
        $this->io->success(sprintf('Executed %d statements', count($statements)));
        return true;
    }
}
