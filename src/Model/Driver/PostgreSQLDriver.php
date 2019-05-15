<?php
namespace Origin\Model\Driver;

use Origin\Model\Datasource;
use Origin\Exception\Exception;

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
