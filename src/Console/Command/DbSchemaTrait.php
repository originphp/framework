<?php
declare(strict_types = 1);
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

namespace Origin\Console\Command;

use Origin\Utility\Inflector;
use Origin\Model\Datasource;
use Origin\Model\ConnectionManager;
use Origin\Model\Exception\DatasourceException;

trait DbSchemaTrait
{
    
    /**
     * Gets the filename for the schema
     *
     * @param string $name schema or Plugin.schema
     * @return string
     */
    public function schemaFilename(string $name, string $extension = 'sql') : string
    {
        list($plugin, $file) = pluginSplit($name);
        if ($plugin) {
            return PLUGINS . DS . Inflector::underscored($plugin) . DS . 'database' . DS .  $file . '.' . $extension;
        }

        return APP . DS . 'database' . DS . $file . '.' . $extension;
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
        $sql = preg_replace('!/\*.*?\*/!s', '', $sql);
        $sql = preg_replace('/^-- .*$/m', '', $sql); // Remove Comment line starting with --
        $sql = preg_replace('/^#.*$/m', '', $sql); // Remove Comments start with #
  
        $statements = [];
        if ($sql) {
            $statements = explode(";\n", $sql);
            $statements = array_map('trim', $statements);
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
        if (! file_exists($filename)) {
            $this->throwError("File {$filename} not found");
        }
        $this->io->info("Loading {$filename}");
       
        if (! ConnectionManager::config($datasource)) {
            $this->throwError("{$datasource} datasource not found");
        }
        $connection = ConnectionManager::get($datasource);
        
        $statement = file_get_contents($filename);
        $statements = $this->parseSql($statement);

        $count = $this->executeStatements($statements, $connection);

        $this->io->success(sprintf('Executed %d statements', $count));

        return true;
    }

    /**
    * Runs a set of statments against a datasource
    *
    * @param array $statements
    * @param Datasource $connection
    * @return integer
    */
    protected function executeStatements(array $statements, Datasource $connection) : int
    {
        $connection->begin();
        $connection->disableForeignKeyConstraints();
        
        foreach ($statements as $statement) {
            try {
                $connection->execute($statement);
            } catch (DatasourceException $ex) {
                $connection->rollback();
                $this->io->status('error', str_replace("\n", '', $statement));
                $this->throwError('Executing query failed', $ex->getMessage());
            }
            $this->io->status('ok', str_replace("\n", '', $statement));
        }
       
        $connection->commit();
        $connection->enableForeignKeyConstraints();

        return count($statements);
    }
}
