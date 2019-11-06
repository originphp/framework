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
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Model;

use Origin\Core\HookTrait;

class Seed
{
    use HookTrait;
    
    public function __construct()
    {
        $this->executeHook('initialize');
    }
    /**
     * Creates the SQL statements for inserting
     *
     * @param \Origin\Model\Connection $connection
     * @return array
     */
    public function insertSql(Connection $connection) : array
    {
        $out = [];
        $properties = get_object_vars($this);
        foreach (array_keys($properties) as $table) {
            foreach ($this->$table as $record) {
                $builder = $connection->queryBuilder($table);
                $sql = $builder->insert($record)->write();
                $out[] = [$sql,$builder->getValues()];
            }
        }

        return $out;
    }
}
