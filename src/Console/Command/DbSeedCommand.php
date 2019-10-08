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
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Console\Command;

use Origin\Core\Config;
use Origin\Utility\Inflector;
use Origin\Model\Datasource;
use Origin\Model\ConnectionManager;
use Origin\Model\Exception\DatasourceException;

class DbSeedCommand extends Command
{
    use DbSchemaTrait;
    protected $name = 'db:seed';

    protected $description = 'Seeds the database with initial records';

    public function initialize() : void
    {
        $this->addOption('connection', [
            'description' => 'Use a different datasource',
            'short' => 'c',
            'default' => 'default',
        ]);
        $this->addArgument('name', [
            'description' => 'seed or Plugin.seed',
        ]);
        $this->addOption('type', [
            'description' => 'Wether to use sql or php',
            'default' => Config::read('Schema.format'),
        ]);
    }
 
    public function execute() : void
    {
        $name = $this->arguments('name') ?? 'seed';
          
        $datasource = $this->options('connection');
        $type = $this->options('type');
        $filename = $this->schemaFilename($name, $type);
        
        if ($type === 'php') {
            $this->loadPHPSeed($name, $filename, $datasource);
        } else {
            $this->loadSchema($filename, $datasource);
        }
    }

    protected function loadPHPSeed(string $name, string $filename, string $datasource) : void
    {
        if (! file_exists($filename)) {
            $this->throwError("File {$filename} not found");
        }
        $this->io->info("Loading {$filename}");
       
        if (! ConnectionManager::config($datasource)) {
            $this->throwError("{$datasource} datasource not found");
        }
        $connection = ConnectionManager::get($datasource);

        list($plugin, $name) = pluginSplit($name);
        $class = 'ApplicationSeed';
        if ($name !== 'seed') {
            $class = Inflector::studlyCaps($name) . 'Seed';
        }
       
        include_once $filename;
        $seed = new $class;
      
        $statements = $seed->insertSql($connection);
        $count = $this->executePreparedStatements($statements, $connection);
   
        $this->io->success(sprintf('Executed %d statements', $count));
    }

    /**
    * Runs a set of statments against a datasource
    *
    * @param array $statements
    * @param Datasource $connection
    * @return integer
    */
    protected function executePreparedStatements(array $statements, Datasource $connection) : int
    {
        $connection->begin();
        $connection->disableForeignKeyConstraints();

        foreach ($statements as $statement) {
            try {
                $sql = $this->unprepare($statement[0], $statement[1]);
                $connection->execute($statement[0], $statement[1]);

                $this->io->status('ok', $sql);
            } catch (DatasourceException $ex) {
                $connection->rollback();
                $this->io->status('error', $sql);
                $this->throwError('Executing query failed', $ex->getMessage());
            }
        }
        $connection->enableForeignKeyConstraints();
        $connection->commit();
      
        return count($statements);
    }

    /**
     * This has been taken from Datasource:unprepare.
     *
     * @param string $sql
     * @param array $params
     * @return string
     */
    protected function unprepare(string $sql, array  $params) : string
    {
        foreach ($params as $needle => $replace) {
            if (is_string($replace)) {
                $replace = "'{$replace}'";
            }
            $sql = preg_replace("/\B:{$needle}/", $replace, $sql);
        }

        return $sql;
    }
}
