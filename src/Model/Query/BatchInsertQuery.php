<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace App\Model\Query;

use Origin\Model\Model;
use InvalidArgumentException;
use Origin\Model\Query\QueryObject;

class BatchInsertQuery extends QueryObject
{
    protected function initialize(Model $model): void
    {
        $this->Model = $model;
    }

    /**
     * Runs a batch insert query. Note there is a limit on number of placeholderes.
     *
     * @param string $table
     * @param array $records
     * @param array $options The following options keys are supported
     *   - transactions: default true. To wrap in begin/commit
     * @return bool|array|null
     */

    public function execute(string $table, array $records, array $options = [])
    {
        $options += ['transaction' => true];
        $fields = $questionMarks = $values = $buffer = [];

        if (empty($records)) {
            throw new InvalidArgumentException('No records');
        }

        $firstKey = array_key_first($records);
        $fields = array_keys($records[$firstKey]);
        $questionMarks = array_fill(0, count($fields), '?');

        foreach ($records as $record) {
            $values = array_merge($values, array_values($record));
            $buffer[] = '(' . implode(', ', $questionMarks) . ')';
        }

        $fields = implode(', ', $fields);
        $buffer = implode(', ', $buffer);

        if ($options['transaction']) {
            $this->Model->begin();
        }

        $result = $this->executeQuery("INSERT INTO {$table} ({$fields}) VALUES {$buffer}", $values);
        if ($result) {
            if ($options['transaction']) {
                $this->Model->commit();
            }

            return $result;
        }
        if ($options['transaction']) {
            $this->Model->rollback();
        }
    }

    protected function executeQuery(string $query, array $values = [])
    {
        return $this->Model->query($query, $values);
    }
}
