<?php 
namespace Origin\Model\Driver;

use Origin\Model\Datasource;
use Origin\Exception\Exception;

/*
In progress not working properly, user as its reserved word. This causes tons of problems:
Cant quote join condition
SELECT
    Bookmark.id,
    Bookmark.user_id,
    Bookmark.title,
    Bookmark.description,
    Bookmark.url,
    Bookmark.category,
    Bookmark.created,
    Bookmark.modified,
    User.id,
    User.name,
    User.email,
    User.password,
    User.dob,
    User.created,
    User.modified
FROM
    `bookmarks` Bookmark
    LEFT JOIN `users` as User ON (Bookmark.user_id = User.id)
WHERE
    Bookmark.id = '1'
LIMIT 1
Second problem is is from with back tick, although that ca easily be changed
*/

class PostgreSQLDriver
{
    /**
    * Holds the Connection
    *
    * @var Origin\Model\Connection
    */
    protected $connection;
    
    /**
     * Database name
     *
     * @var string
     */
    protected $database = null;

    /**
     * Postgre column definitions
     *
     * @var array
     */
    protected $columns = [
        'primary' => ['name' => 'SERIAL NOT NULL'],
        'string' => ['name' => 'VARCHAR', 'length' => 255],
        'text' => ['name' => 'TEXT'],
        'integer' => ['name' => 'INTEGER'],
        'biginteger' => ['name' => 'BIGINT', 'length' => 20],
        'float' => ['name' => 'FLOAT', 'length' => 10, 'precision' => 0], // mysql defaults
        'decimal' => ['name' => 'DECIMAL', 'length' => 10, 'precision' => 0],
        'datetime' => ['name' => 'TIMESTAMP'],
        'timestamp' => ['name' => 'TIMESTAMP'],
        'date' => ['name' => 'DATE'],
        'time' => ['name' => 'TIME'],
        'binary' => ['name' => 'BYTEA'],
        'boolean' => ['name' => 'BOOLEAN'],
    ];

    public function __construct(Datasource $datasource, array $config=[])
    {
        $this->datasource = $datasource;
        $this->database = $config['database'];
    }

    /**
     * Returns the DSN string
     *
     * @param array $config
     * @return string
     */
    public function dsn(array $config) : string
    {
        extract($config);
        return "{$engine}:host={$host};dbname={$database};options='--client_encoding=UTF8'";
    }
    /**
     * Cache when not in debug mode
     *
     * @param string $table
     * @return array
     */
    public function describe(string $table) : array
    {
        $sql = 'SELECT DISTINCT column_name AS name, data_type AS type, character_maximum_length AS "char_length",numeric_precision AS "num_length",numeric_scale AS "num_precision", column_default AS default,  is_nullable AS "null",character_octet_length AS oct_length, ordinal_position AS position FROM information_schema.columns
        WHERE table_name = \''.$table.'\' AND table_catalog = \''.$this->database.'\' ORDER BY position';
         
        $schema = [];

        if ($this->datasource->execute($sql)) {
            $results =  $this->datasource->fetchAll();
         
           
            foreach ($results as $result) {
                $data = ['type'=>null,'length'=>null,'precision'=>null];
                $data['type'] = $this->column($result['type']);
                if ($data['type']=== 'string') {
                    $data['length'] = $result['char_length'];
                } elseif (in_array($data['type'], ['integer','decimal','float'])) {
                    $data['length'] = $result['num_length'];
                    $data['precision'] = $result['num_precision'];
                }
                $data['default'] = $result['default']? $result['default']:null;
                $data['key'] = $result['name']==='id' and $data['type'] === 'integer' ?'primary':null; // Assume id is primary key
                $schema[$result['name']] = $data;
            }
        }
            
        return $schema;
    }

    /**
     * Try to map types
     *
     * @param string $type
     * @return void
     */
    public function column(string $type)
    {
        if (in_array($type, ['integer','text','date','time','boolean'])) {
            return $type;
        }
        // Char and varchar
        if (strpos($type, 'character') !== false or $type ==='uuid') {
            return 'string';
        }
        if (strpos($type, 'timestamp') !== false) {
            return 'datetime';
        }
        if (in_array($type, ['decimal','numeric'])) {
            return 'decimal';
        }
        if (strpos($type, 'time') !== false) { // time without time zone,with etc
            return 'time';
        }
        if (strpos($type, 'bytea') !== false) {
            return 'binary';
        }
        if ($type ==='bigint') {
            return 'biginteger';
        }
        
        if (strpos($type, 'float') !== false or strpos($type, 'double') !== false) {
            return 'float';
        }
        // How did you get here? maybe something was missed let me know
        return 'string';
    }
    /**
     * Returns an array of tables
     *
     * @return array
     */
    public function tables() : array
    {
        $sql = 'SELECT table_name as "table" FROM information_schema.tables WHERE table_catalog = \''.$this->database.'\' AND table_schema=\'public\'';
        if ($this->datasource->execute($sql)) {
            return $this->datasource->fetchList();
        }
        return [];
    }

    /**
    * Returns a MySQL string for creating a table
    *
    * @param string $table
    * @param array $data
    * @return string
    */
    public function createTable(string $table, array $data)
    {
        $result = [];
      
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

            if (!in_array($settings['type'], ['integer','boolean']) and !empty($settings['length'])) {
                if (in_array($settings['type'], ['decimal', 'float'])) {
                    $output .= " ({$settings['length']},{$settings['precision']})";
                } else {
                    $output .= " ({$settings['length']})";
                }
            }

            if (isset($settings['default']) and $settings['type'] !=='boolean') {
                $output .= " DEFAULT {$settings['default']}";
            }

            // When key is set as primary we automatically make it autoincrement
            if (!empty($settings['key']) and $settings['key'] === 'primary') {
                $output .= ' ' . $this->columns['primary']['name'];
            } elseif (isset($settings['null'])) {
                if ($settings['null'] == true) {
                    $output .= ' NULL';
                } else {
                    $output .= ' NOT NULL';
                }
            }
            $result[] = ' '.$output;
        }
        return "CREATE TABLE {$table} (\n".implode(",\n", $result)."\n)";
    }
}
