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
namespace Origin\Model\Driver;

use Origin\Model\Datasource;
use Origin\Exception\Exception;
/**
 * Undocumented class
 */
class PostgreSQLDriver extends Datasource
{
    protected $name = 'pgsql';

     /**
     * What to escape table and column aliases
     *
     * @var string
     */
    protected $escape = '"';

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
            return "{$engine}:host={$host};dbname={$database};options='--client_encoding=UTF8'";
        }
        return "{$engine}:host={$host};options='--client_encoding=UTF8'";
    }
    
    public function enableForeignKeyConstraints(){
        $this->execute('SET CONSTRAINTS ALL IMMEDIATE');
    }

    public function disableForeignKeyConstraints(){
        $this->execute('SET CONSTRAINTS ALL DEFERRED');
    }
}
