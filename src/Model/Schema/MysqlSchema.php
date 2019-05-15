<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

 /**
  * Migrations - This is designed for editing the schema, sometimes data might need to modified but
  * it should not be used to insert data. (if you have too then use connection manager)
  * There are suttle changes here, so this cannot be just droped in model driver. E.g. decimal and numeric does not have limit
  *
  */
namespace Origin\Model\Schema;

use Origin\Model\ConnectionManager;
use Origin\Exception\Exception;
use Origin\Model\Schema\BaseSchema;

class MysqlSchema extends BaseSchema
{
    /**
         * This is the map for database agnostic, if its not found here then
         * use what user supplies. @important. This will allow using char and medium text when testing
         *
         * @var array
         */
    protected $columns = [
        'primaryKey' => ['name' => 'INT NOT NULL AUTO_INCREMENT'],
        'string' => ['name' => 'varchar', 'limit' => 255],
        'text' => ['name' => 'text'],
        'integer' => ['name' => 'int'],
        'bigint' => ['name' => 'bigint', 'limit' => 20], // chgabged
        'float' => ['name' => 'FLOAT', 'precision' => 10, 'scale' => 0], // mysql defaults
        'decimal' => ['name' => 'DECIMAL', 'precision' => 10, 'scale' => 0],
        'datetime' => ['name' => 'DATETIME'],
        'time' => ['name' => 'TIME'],
        'timestamp' => ['name' => 'TIMESTAMP'],
        'date' => ['name' => 'DATE'],
        'binary' => ['name' => 'BLOB'],
        'boolean' => ['name' => 'TINYINT', 'limit' => 1],
    ];

    public function schema(string $table) : array
    {
        $schema = [];
        $results = $this->fetchAll("SHOW FULL COLUMNS FROM {$table}");

        $reverseMapping = [];
        foreach ($this->columns as $key => $value) {
            $reverseMapping[strtolower($value['name'])] = $key;
        }
        /**
        * @todo refactor to work similar to Postgres,not using reverse mapping. For cleanner
        * code. These are temporary solutions.
        * @see MySQL driver - duplicated there.
        */
        $reverseMapping['char'] = $reverseMapping['varchar']; // add missing type
          $reverseMapping['mediumtext'] = $reverseMapping['text']; // add missing type
          $reverseMapping['longtext'] = $reverseMapping['text']; // add missing type
      
          foreach ($results as $column) {
              $decimals = $length = null;
              $type = str_replace(')', '', $column['Type']);
              if (strpos($type, '(') !== false) {
                  list($type, $length) = explode('(', $type);
                  if (strpos($length, ',') !== false) {
                      list($length, $decimals) = explode(',', $length);
                  }
              }
             
              if (isset($reverseMapping[$type])) {
                  $type = $reverseMapping[$type];
                  $schema[$column['Field']] = [
                      'type' => $type,
                      'limit' => ($length and !in_array($type, ['boolean','decimal','numeric']))?(int) $length:null,
                      'default' => $column['Default'],
                      'null' => ($column['Null'] === 'YES' ? true : false),
                  ];
            
                  if (in_array($type, ['float','decimal'])) {
                      $schema[$column['Field']]['precision'] = $length;
                      $schema[$column['Field']]['scale'] = (int) $decimals;
                  }
                  if ($schema[$column['Field']]['limit'] === null) {
                      unset($schema[$column['Field']]['limit']);
                  }
                  if (in_array($type, ['timestamp','datetime'])) {
                      $schema[$column['Field']]['default'] = null; // remove current_timestamp
                  }
                        
                  if ($column['Key'] === 'PRI' AND $column['Extra'] === 'auto_increment') {
                    $schema[$column['Field']]['type'] = 'primaryKey';
                  }
                  /**
                   * @todo in postgresql cant get this work yet.
                   */
                  if($column['Key'] === 'PRI'){
                    $schema[$column['Field']]['key'] = 'primary';
                  }
              }
          }
        return $schema;
    }

    public function tables(){
        $tables = [];
        $results = $this->fetchAll('SHOW TABLES');
        if ($results) {
            foreach ($results as $value) {
                $tables[] = current($value);
            }
        }
        return $tables;
    }

    public function indexes(string $table)
    {
        $config = ConnectionManager::config($this->datasource);
        $sql = "SHOW INDEX FROM {$table}";
        $results = $this->fetchAll($sql);
        $indexes  = [];
        foreach ($results as &$result) {
            $result = array_change_key_case($result, CASE_LOWER);
            $indexes[] = [
                'name' => $result['key_name'],
                'column' => $result['column_name'],
                'unique' => ($result['non_unique'] == 0)? true:false
           ];
        }
        return  $indexes;
    }

    public function renameTable(string $from, string $to)
    {
        // ALTER TABLE tableName CHANGE `oldcolname` `newcolname` datatype(length); work with 5,7
        return  "RENAME TABLE {$from} TO {$to}";
    }

    /**
   * Changes a column according to the new definition
   *
   * @param string $table
   * @param string $name
   * @param array $options
   * @return string
   */
    public function changeColumn(string $table, string $name, string $type, array $options=[])
    {
        $definition = $this->buildColumn(array_merge(['name'=>$name,'type'=>$type], $options));
        return "ALTER TABLE {$table} MODIFY COLUMN {$definition}";
    }

    /**
     * Removes an index on table
     *
     * @param string $table
     * @param string|array $column owner_id, [owner_id,tenant_id]
     * @param array $options
     *  - name: name of index
     * @return string
     */
    public function removeIndex(string $table, string $name)
    {
        return "DROP INDEX {$name} ON {$table}";
    }


    /**
     * Renames an index
     * @requires MySQL 5.7+
     *
     * @param string $table
     * @param string $oldName
     * @param string $newName
     * @return void
     */
    public function renameIndex(string $table, string $oldName, string $newName)
    {
        return "ALTER TABLE {$table} RENAME INDEX {$oldName} TO {$newName}";
    }

    public function foreignKeys(string $table)
    {
        $config = ConnectionManager::config($this->datasource);

        $sql = "SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = '{$table}';";

        $results = $this->fetchAll($sql);
        foreach ($results as &$result) {
            $result = array_change_key_case($result, CASE_LOWER);
        }
        return $results;
    }

    public function removeForeignKey(string $fromTable, $constraint)
    {
        return "ALTER TABLE {$fromTable} DROP FOREIGN KEY {$constraint}";
    }
    public function showCreateTable(string $table)
    {
        $result = $this->fetchRow("SHOW CREATE TABLE {$table}");
        if(!empty($result['Create Table'])){
            return $result['Create Table'];
        }
        return null;
    }
}
