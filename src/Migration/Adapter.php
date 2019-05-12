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
  *
  */
namespace Origin\Migration;

use Origin\Model\ConnectionManager;
use Origin\Exception\Exception;

class Adapter
{
 /**
     * Fetchs a single row from the database
     *
     * @param string $sql
     * @return array|null
     */
    public function fetchRow(string $sql){
      $connection = $this->connection();
      $connection->execute($sql);
      return $connection->fetch();
  }

   /**
   * Fetchs all rows from database
   *
   * @param string $sql
   * @return array
   */
  public function fetchAll(string $sql){
      $connection = $this->connection();
      $connection->execute($sql);
      $results = $connection->fetchAll();
      if($results){
          return $results;
      }
      return [];
  }

    /**
     * Returns the ConnectionManager
     *
     * @return \Origin\Model\ConnectionManager
     */
    public function connection(){
      return ConnectionManager::get($this->datasource);
  }
}