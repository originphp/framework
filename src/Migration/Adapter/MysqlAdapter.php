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
namespace Origin\Migration\Adapter;

use Origin\Model\ConnectionManager;
use Origin\Exception\Exception;
use Origin\Migration\Adapter;

class MysqlAdapter extends Adapter
{
/**
     * This is the map for database agnostic, if its not found here then
     * use what user supplies
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
        'date' => ['name' => 'DATE'],
        'binary' => ['name' => 'BLOB'],
        'boolean' => ['name' => 'TINYINT', 'limit' => 1],
    ];

    /**
     * Build a native sql column string
     *
     * @param array $column 
     *  - name: name of column
     *  - type: integer,bigint,float,decimail,datetime,date,binary or boolean
     *  - limit: length of field
     *  - precision: for decimals and floasts
     *  - default: default value use '' or nill for null value
     *  - null: allow null values
     * @return void
     */
    public function buildColumn(array $column){
        $column += [
            'name' => null,
            'type' => null, 
            'limit'=> null, // Max column limit [text, binary, integer]
            'precision'=> null, // decima, float
            'null' => null,
        ];
    
        if(empty($column['name'])){
            throw new Exception('Column name not specificied');
        }
        if(empty($column['type'])){
            throw new Exception('Column type not specificied');
        }
        $real = [];
        $type = $column['type'];
        if(isset($this->columns[$type])){
            $real = $this->columns[$type];
            $type = strtoupper($this->columns[$type]['name']);
        }
       
        # Lengths
        $output = $this->columnName($column['name']) . ' ' . $type;
       
        /**
         * Logic when using agnostic
         * Get defaults if needed
         */
        if($real){
            if(!empty($real['limit']) AND empty($column['limit'])){
                $column['limit'] = $real['limit'];
            }
            if(isset($real['precision']) AND !isset($column['precision'])){
                $column['precision'] = $real['precision'];
            }
            if(isset($real['scale']) AND !isset($column['scale'])){
                $column['scale'] = $real['scale'];
            }
        }
        
        if($column['limit']){
            $output .= "({$column['limit']})";
        }
        elseif(!empty($column['precision'])){
            $output .= "({$column['precision']},{$column['scale']})";
        }

        if(isset($column['default']) AND ($column['default'] === '' OR $column['default'] ==='nil')){
            $column['default'] = null;
        }
         
        /**
         * First handle defaults, then nulls
         */
        if(isset($column['default']) AND $column['null'] === false){
            $output .= ' DEFAULT ' . $this->columnValue($column['default']) .' NOT NULL';
        }
        elseif(isset($column['default'])) {
            $output .= ' DEFAULT ' . $this->columnValue($column['default']);
        }
        elseif($column['null']) {
            $output .= ' DEFAULT NULL'; 
        }
        elseif($column['null'] === false){
            $output .= ' NOT NULL';
        }
       
        return $output;
    }

     /**
     * Returns a MySQL string for creating a table
     *
     * @param string $table
     * @param array $data
     * @return string
     */
    public function createTable(string $table, array $data,array $options=[]) : string
    {
        $append = '';
        if(!empty($options['options'])){
            $append = ' '. $options['options'];
        }

        $result = [];

        $primaryKeys = [];
        foreach ($data as $field => $settings) {
            if (!empty($settings['key'])) {
                $primaryKeys[] = $field;
            }
        }

        foreach ($data as $field => $settings) {
            if (is_string($settings)) {
                $settings = ['type' => $settings];
            }
            // User input or from db
            if($settings['type'] === 'primaryKey'){
                $primaryKeys[] = $field;
            }

            $mapping = ['name'=>$settings['type']]; // non agnostic
            if (isset($this->columns[$settings['type']])) {
                $mapping = $this->columns[$settings['type']];
            }

            $settings = $settings + $mapping;

            $output = $field . ' ' .  strtoupper($mapping['name']);
           
            if (!empty($settings['limit'])) {
                if (in_array($settings['type'], ['decimal', 'float'])) {
                    $output .= "({$settings['precision']},{$settings['scale']})";
                } else {
                    $output .= "({$settings['limit']})";
                }
            }

            if (isset($settings['default'])) {
                $output .= " DEFAULT '{$settings['default']}'";
            }

            if (isset($settings['null'])) {
                if ($settings['null'] == true) {
                    $output .= ' NULL';
                } else {
                    $output .= ' NOT NULL';
                }
            }
            $result[] = ' '.$output;
        }
        
        if($primaryKeys){
            $result[] = ' PRIMARY KEY ('.implode(',', $primaryKeys).')';
        }
    
        return "CREATE TABLE {$table} (\n".implode(",\n", $result)."\n){$append}";
    }

    /**
     * Adds a column to an existing table
     * 
    * @param string $table table name
     * @param string $name column name
     * @param string $type (primaryKey,string,text,integer,bigint,float,decimal,datetime,time,date,binary,boolean)
     * @param array $options The following options keys can be used:
     *   - limit: limits the column length for string and bytes for text,binary,and integer
     *   - default: the default value, use '' or nill for null
     *   - null: allows or disallows null values to be used
     *   - precision: the precision for the number (places to before the decimal point)
     *   - scale: the numbers after the decimal point
     */
    public function addColumn(string $table,string $name, string $type,array $options=[]){
        $definition = $this->buildColumn(array_merge(['name'=>$name,'type'=>$type],$options));
        return "ALTER TABLE {$table} ADD COLUMN {$definition}";
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
    public function addIndex(string $table,string $column,string $name, array $options=[]) : string
    {
        if(!empty($options['unique'])){
            return "CREATE UNIQUE INDEX {$name} ON {$table} ({$column})";
        }
        return "CREATE INDEX {$name} ON {$table} ({$column})";
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
    public function removeIndex(string $table,string $name){
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
    public function renameIndex(string $table,string $oldName,string $newName){
        return "ALTER TABLE {$table} RENAME INDEX {$oldName} TO {$newName}";
    }

   
    public function addForeignKey(string $fromTable,string $toTable,array $options=[]){
        return "ALTER TABLE {$fromTable} ADD CONSTRAINT {$options['name']} FOREIGN KEY ({$options['column']}) REFERENCES {$toTable} ({$options['primaryKey']})";
    }   

    public function removeForeignKey(string $fromTable,$constraint){
        return "ALTER TABLE {$fromTable} DROP FOREIGN KEY {$constraint}";
    }

    public function foreignKeys(string $table){
        $config = ConnectionManager::config($this->datasource);
       
        $sql = "SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
      FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
      WHERE REFERENCED_TABLE_SCHEMA = '{$config['database']}'
      AND TABLE_NAME = '{$table}';";
  
        $results = $this->fetchAll($sql);
        foreach($results as &$result){
           $result = array_change_key_case($result,CASE_LOWER);
        }
        return $results;
    }

    public function indexes(string $table){
        $config = ConnectionManager::config($this->datasource);
        $sql = "SHOW INDEX FROM {$table}";
        $results = $this->fetchAll($sql);
        $indexes  = [];
        foreach($results as &$result){
           $result = array_change_key_case($result,CASE_LOWER);
           $indexes[] = [
                'name' => $result['key_name'],
                'column' => $result['column_name'],
                'unique' => ($result['non_unique'] == 0)? true:false
           ];
        }
        return  $indexes;
    }

    /**
     * Checks if a foreignKey exists
     *
     * @param string $table
     * @param string $foreignKey
     * @return bool
     */
    public function foreignKeyExists(string $table,array $options){
       
        $options += ['column'=>null,'name'=>null];
        $foreignKeys = $this->foreignKeys($table);
       
        foreach($foreignKeys as $fk){
            if($options['column'] AND $fk['column_name'] == $options['column']){
                return true;
            }
            if($options['name'] AND $fk['constraint_name'] == $options['name']){
                return true;
            }
        }
        return false;
    }

    /**
     * Changes a column according to the new definition
     *
     * @param string $table
     * @param string $name
     * @param array $options
     * @return string
     */
    public function changeColumn(string $table,string $name, string $type,array $options=[]){
        $definition = $this->buildColumn(array_merge(['name'=>$name,'type'=>$type],$options));
        return "ALTER TABLE {$table} MODIFY COLUMN {$definition}";
    }

/**
     * Drops a table from database
     *
     * @param string $table
     * @return string
     */
    public function dropTable(string $table){
       return "DROP TABLE {$table}";
     }
 
     /**
      * Renames a table
      *
      * @param string $from
      * @param string $to
      * @return string
      */
     public function renameTable(string $from,string $to){
         // ALTER TABLE tableName CHANGE `oldcolname` `newcolname` datatype(length); work with 5,7
         return  "RENAME TABLE {$from} TO {$to}";
     }
 
     /**
      * Renames a column name
      *
      * @param string $table
      * @param string $from
      * @param string $to
      * @return string
      */
     public function renameColumn(string $table, string $from,string $to){
         return  "ALTER TABLE {$table} RENAME COLUMN {$from} TO {$to}";
     }
 
     /**
      * Removes a column from the table§
      *
      * @param string $table
      * @param string $column
      * @return string
      */
     public function removeColumn(string $table,string $column){
        return "ALTER TABLE {$table} DROP COLUMN {$column}";
     }
 
     /**
      * Removes multiple columns from the table
      *
      * @param string $table
      * @param array $columns
      * @return string
      */
     public function removeColumns(string $table,array $columns){
         $sql = "ALTER TABLE {$table}";
         foreach($columns as $column){
             $sql .="\nDROP COLUMN {$column},";
         }
         return substr($sql,0,-1);
     }
 
     /**
      * Returns an list of tables
      *
      * @return array
      */
     public function tables(){
        $tables = [];
        $results = $this->fetchAll('SHOW TABLES');
        foreach ($results as $value) {
            $tables[] = current($value);
        }
        return $tables;
     }
 
     /**
      * Checks if a table exists
      *
      * @param string $name
      * @return void
      */
     public function tableExists(string $name){
         return in_array($name,$this->tables());
     }
 
     /**
      * Returns an array of columns
      *
      * @param string $table
      * @return array
      */
     public function columns(string $table){
         $schema = $this->schema($table);
         return array_keys($schema);
     }
 
     /**
      * Checks if a column exists in a table
      *
      * @param string $table
      * @param string $column
      * @return bool
      */
     public function columnExists(string $table,string $column){
         return in_array($column,$this->columns($table));
     }

   

    /**
     * Return column name
     *
     * @param string $name
     * @return void
     */
    public function columnName(string $name){
        return $name;
    }

    public function columnValue($value){
        if($value === null){
            $value = 'NULL';
        }
        if(is_int($value)){
            return $value;
        }
        return "'{$value}'";
    }

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
             * code
             */
            $reverseMapping['char'] = $reverseMapping['varchar']; // add missing type

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
                        'limit' => ($length and !in_array($type,['boolean','decimal','numeric']))?(int) $length:null,
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
                    /**
                     * @todo add back unsigined
                     */
                    
                    if ($column['Key'] === 'PRI') {
                        $schema[$column['Field']]['key'] = 'primary';
                    }
                    /**
                     * Not sure about this due to new primaryKey setting
                     */
                    if ($column['Extra'] === 'auto_increment') {
                        $schema[$column['Field']]['autoIncrement'] = true;
                    }
                }
            }
 
        return $schema;
    }

}