<?php 
namespace Origin\Model\Driver;

use Origin\Model\Datasource;
use Origin\Exception\Exception;

class MySQLDriver extends Datasource
{
    /**
     * Holds the Connection
     *
     * @var Origin\Model\Connection
     */
    protected $connection;
    
    /**
     * MySQL column definitions
     *
     * @var array
     */
    protected $columns = [
        'autoIncrement' => ['name' => 'INT AUTO_INCREMENT'],
        'string' => ['name' => 'VARCHAR', 'length' => 255],
        'text' => ['name' => 'TEXT'],
        'integer' => ['name' => 'INT'],
        'biginteger' => ['name' => 'BIGINT', 'length' => 20],
        'float' => ['name' => 'FLOAT', 'length' => 10, 'precision' => 0], // mysql defaults
        'decimal' => ['name' => 'DECIMAL', 'length' => 10, 'precision' => 0],
        'datetime' => ['name' => 'DATETIME'],
        'timestamp' => ['name' => 'TIMESTAMP'],
        'date' => ['name' => 'DATE'],
        'time' => ['name' => 'TIME'],
        'binary' => ['name' => 'BLOB'],
        'boolean' => ['name' => 'TINYINT', 'length' => 1],
    ];

 
    /**
     * What to escape table and column aliases
     *
     * @var string
     */
    protected $escape = '`';

    /**
     * Returns the DSN string
     *
     * @param array $config
     * @return string
     */
    public function dsn(array $config) : string
    {
        extract($config);
        if($database){
            return "{$engine}:host={$host};dbname={$database};charset=utf8mb4";
        }
        return  "{$engine}:host={$host};charset=utf8mb4";
    }
    
    /**
     * @internal This code has been duplicated in new MysqlAdapter. Once that is
     * ready this will be depreciated. Changes here should be made there as well.
     */
    public function schema(string $table) : array
    {
        $schema = [];
        $this->execute("SHOW FULL COLUMNS FROM {$table};");
        $results = $this->fetchAll();
        if ($results) {
            
            $reverseMapping = [];
            foreach ($this->columns as $key => $value) {
                $reverseMapping[strtolower($value['name'])] = $key;
            }
            /**
             * @todo refactor to work similar to Postgres,not using reverse mapping. For cleanner
             * code. These are temporary solutions.
             */
            $reverseMapping['char'] = $reverseMapping['varchar']; // add missing type
            $reverseMapping['mediumtext'] = $reverseMapping['text']; // add missing type
            $reverseMapping['longtext'] = $reverseMapping['text']; // add missing type

            foreach ($results as $column) {
                $precision = $length = null;
                $type = str_replace(')', '', $column['Type']);
                if (strpos($type, '(') !== false) {
                    list($type, $length) = explode('(', $type);
                    if (strpos($length, ',') !== false) {
                        list($length, $precision) = explode(',', $length);
                    }
                }


                if (isset($reverseMapping[$type])) {
                    $type = $reverseMapping[$type];
                    $schema[$column['Field']] = [
                        'type' => $type,
                        'length' => ($length and $type !='boolean')?(int) $length:null,
                        'default' => $column['Default'],
                        'null' => ($column['Null'] === 'YES' ? true : false),
                    ];
              
                    if (in_array($type, ['float','decimal'])) {
                        $schema[$column['Field']]['precision'] = $precision;
                    }
                    if ($schema[$column['Field']]['length'] === null) {
                        unset($schema[$column['Field']]['length']);
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
                    if ($column['Extra'] === 'auto_increment') {
                        $schema[$column['Field']]['autoIncrement'] = true;
                    }
                }
            }
        }

        return $schema;
    }
  
    /**
     * Returns an array of tables
     *
     * @return array
     */
    public function tables() : array
    {
        $tables = [];
        $this->execute('SHOW TABLES');
        $results = $this->fetchAll();
        if ($results) {
            foreach ($results as $value) {
                $tables[] = current($value);
            }
        }
        return $tables;
    }

    /**
     * Returns a MySQL string for creating a table
     *
     * @param string $table
     * @param array $data
     * @return string
     */
    public function createTable(string $table, array $data) : string
    {
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

            $mapping = null;
            if (isset($this->columns[$settings['type']])) {
                $mapping = $this->columns[$settings['type']];
            }

            if (!$mapping) {
                throw new Exception("Unkown column type '{$settings['type']}'");
            }

            $settings = $settings + $mapping;

            $output = "{$field} {$mapping['name']}";
           
            if (!empty($settings['length'])) {
                if (in_array($settings['type'], ['decimal', 'float'])) {
                    $output .= "({$settings['length']},{$settings['precision']})";
                } else {
                    $output .= "({$settings['length']})";
                }
            }

            if (isset($settings['default'])) {
                $output .= " DEFAULT '{$settings['default']}'";
            }
            if (!empty($settings['autoIncrement'])) {
                $output .= " AUTO_INCREMENT";
            }

            if (!empty($settings['key']) and count($primaryKeys)===1) {
                $output .= " PRIMARY KEY";
                if (!empty($settings['autoIncrement']) and $settings['autoIncrement']) {
                    unset($settings['null']);
                }
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
        if (count($primaryKeys)>1) {
            $result[] = ' PRIMARY KEY ('.implode(',', $primaryKeys).')';
        }
        return "CREATE TABLE {$table} (\n".implode(",\n", $result)."\n)";
    }
}
