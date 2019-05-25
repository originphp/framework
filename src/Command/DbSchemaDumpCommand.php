<?php
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

namespace Origin\Command;
use Origin\Command\Command;
use Origin\Model\ConnectionManager;
use Origin\Model\Exception\DatasourceException;
use Origin\Command\DbSchemaTrait;

class DbSchemaDumpCommand extends Command
{
    use DbSchemaTrait;
    protected $name = 'db:schema:dump';

    protected $description = 'Dumps the schema to a sql file';

    public function initialize(){
        $this->addOption('datasource', [
            'description' => 'Use a different datasource',
            'short' => 'ds',
            'default' => 'default'
            ]);
        $this->addOption('type',[
            'description' => 'How the schema will be dumped, in sql or php',
            'default' => 'sql'
        ]);
        $this->addArgument('name',[
            'description' => 'schema_name or Plugin.schema_name',
            'default' => 'schema'
        ]);
    }
 
    public function execute(){
        $name = $this->arguments('name');
        if($name === null){
            $name = 'schema';
        }

        $datasource = $this->options('datasource');
        if(!ConnectionManager::config($datasource)){
            $this->throwError("{$datasource} datasource not found");
        }

        $type = $this->options('type');
        if(!in_array($type,['sql','php'])){
            $this->throwError(sprintf('The type `%s` is invalid',$type));
        }

        $filename = $this->schemaFilename($name,$type);
        $this->io->info("Dumping schema to {$filename}");
        if($type === 'sql'){
            $this->dump($datasource,$name);
        }
        else{
            $this->dumpPhp($datasource,$name);
        }
       
    }


    protected function dumpPhp(string $datasource,string $name)
    {

        $connection = ConnectionManager::get($datasource);
        $dump = [];
        $filename = $this->schemaFilename($name,'php');
        
        /**
         * @internal if issues arrise with PostgreSQL then switch here to pg_dump
         */
        $schema = [];
        foreach ($connection->tables() as $table) {
            $schema[$table] = $connection->adapter()->schema($table);
            $this->io->list($table);
        }
        $data = '<?php' . "\n" . '$schema = ' . $this->varExport($schema) . ';';

        if(!$this->io->createFile($filename,$data)){
            $this->throwError('Error saving schema file');
        }
    }

    protected function varExport(array $data)
    {
        $schema = var_export($data, true);
        $schema = str_replace(
            ['array (', '),', " => \n", '=>   ['],
            ['[', '],', ' => ', '=> ['], $schema);

        return substr($schema, 0, -1).']';
    }

    protected function dump(string $datasource,string $name)
    {
        $connection = ConnectionManager::get($datasource);
        $dump = [];
        if($connection->engine()==='mysql'){
            $dump[] = "SET FOREIGN_KEY_CHECKS=0;";
        }
        $filename = $this->schemaFilename($name,'sql');
     
        /**
         * @internal if issues arrise with PostgreSQL then switch here to pg_dump
         */
        foreach ($connection->tables() as $table) {
            $dump[] = $connection->adapter()->showCreateTable($table) .';';
            $this->io->list($table);
        }
   
        if (!$this->io->createFile($filename, implode("\n\n", $dump))) {
            $this->throwError('Error saving schema file');
        }
    }

}