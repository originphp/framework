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

namespace Origin\Model;

use Origin\Exception\Exception;
use Origin\Model\ConnectionManager;

class Schema
{
    /**
     * Not all databases will support each type.
     * @todo how to deal with MediumText will generate error
     * @todo other field types big int etc
     *
     * @var array
     */
    protected $mapping = array(
            'string' => array('name' => 'VARCHAR', 'length' => 255),
            'text' => array('name' => 'TEXT'),
            'integer' => array('name' => 'INT'),
            'float' => array('name' => 'FLOAT', 'length' => 10, 'precision' => 0), // mysql defaults
            'decimal' => array('name' => 'DECIMAL', 'length' => 10, 'precision' => 0),
            'datetime' => array('name' => 'DATETIME'),
            'date' => array('name' => 'DATE'),
        'time' => array('name' => 'TIME'),
            'binary' => array('name' => 'BLOB'),
            'boolean' => array('name' => 'TINYINT', 'length' => 1),
        );

    public function mapping(string $column)
    {
        if (isset($this->mapping[$column])) {
            return $this->mapping[$column];
        }

        return null;
    }

    /**
     * Creates an MySQL create table statement.
     *
     * @param string $table
     * @param array  $data
     *
     * array(
     *      'id' => array('type' => 'integer', 'key' => 'primary'),
     *        'title' => array(
     *          'type' => 'string',
     *           'length' => 120,
     *            'null' => false
     *           ),
     *           'body' => 'text',
     *           'published' => array(
     *             'type' => 'integer',
     *             'default' => '0',
     *             'null' => false
     *           ),
     *           'created' => 'datetime',
     *           'modified' => 'datetime'
     *       );
     *
     * @return string
     */
    public function createTable(string $table, array $data)
    {
        $result = [];

        foreach ($data as $field => $settings) {
            if (is_string($settings)) {
                $settings = ['type' => $settings];
            }

            $mapping = $this->mapping($settings['type']);
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
                if (!empty($settings['unsigned'])) {
                    $output .= " unsigned";
                }
            }

            if (isset($settings['default'])) {
                $output .= " DEFAULT {$settings['default']}";
            }

            if (isset($settings['null'])) {
                if ($settings['null'] == true) {
                    $output .= ' NULL';
                } else {
                    $output .= ' NOT NULL';
                }
            }

            // When key is set as primary we automatically make it autoincrement
            if (!empty($settings['autoIncrement'])) {
                $output .= ' AUTO_INCREMENT PRIMARY KEY';
            }
            $result[] = ' '.$output;
        }

        return "CREATE TABLE {$table} (\n".implode(",\n", $result)."\n)";
    }

    /**
     * Generates the schema as used by createTable
     * @todo this is dumping too much irrelevant info
     * @param array $describe
     * @return void
     */
    public function generate(string $table, string $datasource='default')
    {
        $reverseMapping = [];
        foreach ($this->mapping as $key => $value) {
            $reverseMapping[strtolower($value['name'])] = $key;
        }
      
        $data = [];

        $connection = ConnectionManager::get($datasource);
        $schema = $connection->schema($table);
        foreach ($schema as &$result) {
            $result['type'] = $reverseMapping[$result['type']];
            foreach (['key','unsigned','autoIncrement','length'] as $key) {
                if ($result[$key] == false) {
                    unset($result[$key]);
                }
            }
            if ($result['type'] !== 'decimal') {
                unset($result['precision']);
            }
            if ($result['null'] === false and $result['default'] === null) {
                unset($result['default']);
            }
        }
       
        return $schema;
    }
}
