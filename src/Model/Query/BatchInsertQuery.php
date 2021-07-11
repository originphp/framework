<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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
namespace Origin\Model\Query;

use Origin\Model\Model;
use InvalidArgumentException;

class BatchInsertQuery extends QueryObject
{

    /**
     * @var \Origin\Model\Model
     */
    private $Model;
    
    protected function initialize(Model $model): void
    {
        $this->Model = $model;
    }

    /**
     * Runs a batch insert query. Note there is a limit on number of placeholderes.
     *
     * @param array $records
     * @param array $options The following options keys are supported
     *   - transactions: default true. To wrap insert in a transaction begin/commit
     *   - table: default is the Model table.
     * @return bool|array|null
     */

    public function execute(array $records, array $options = [])
    {
        $options += ['transaction' => true,'table' => $this->Model->table()];
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
            $this->Model->beginTransaction();
        }

        $this->executeQuery("INSERT INTO {$options['table']} ({$fields}) VALUES {$buffer}", $values);

        if ($options['transaction']) {
            $this->Model->commitTransaction();
        }

        return true;
    }

    protected function executeQuery(string $query, array $values = [])
    {
        return $this->Model->query($query, $values);
    }
}
