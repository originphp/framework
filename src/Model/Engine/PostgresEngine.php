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

namespace Origin\Model\Engine;

use Origin\Model\Connection;

class PostgresEngine extends Connection
{
    protected $name = 'postgres';

    /**
     * What to quote table and column aliases
     *
     * @var string
     */
    protected $quote = '"';

    /**
     * Returns the DSN string
     *
     * @param array $config
     * @return string
     */
    public function dsn(array $config): string
    {
        extract($config);
        if ($database) {
            return "pgsql:host={$host};dbname={$database};options='--client_encoding=UTF8'";
        }

        return "pgsql:host={$host};options='--client_encoding=UTF8'";
    }

    /**
     * Gets a list of tables
     *
     * @return array
     */
    public function tables(): array
    {
        $sql = 'SELECT table_name as "table" FROM information_schema.tables WHERE table_schema=\'public\'';

        $out = [];
        if ($this->execute($sql)) {
            $list = $this->fetchList();
            if ($list) {
                $out = $list;
            }
            sort($out); // why sort with db server
        }

        return $out;
    }

    /**
     * Returns a list of databases
     *
     * @return array
     */
    public function databases(): array
    {
        $sql = 'SELECT datname FROM pg_database WHERE datistemplate = false;';
        $out = [];
        if ($this->execute($sql)) {
            $list = $this->fetchList();
            if ($list) {
                $out = $list;
            }
        }

        return $out;
    }
}
