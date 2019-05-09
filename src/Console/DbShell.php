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

namespace Origin\Console;

use Origin\Core\Inflector;
/**
 * @todo think about importing generating plugin stuff. and option parsing
 */
class DbShell extends Shell
{

    public function initialize()
    {
        $this->loadTask('Db');
        $this->addOption('datasource',['help'=>'Use a different datasource','value'=>'name','short'=>'ds']);
       

        $this->addCommand('create',[
            'help' => 'Creates the database',
        ]);
        $this->addCommand('drop',[
            'help' => 'Drops the database',
        ]);
        $this->addCommand('schema',[
            'help' => 'Dumps and loads SQL schema files',
            'arguments' => [
                'action' => ['help'=>'dump or load','required'=>true],
                'file' => ['help'=>'schema or Plugin.schema']
            ]
        ]);
        $this->addCommand('seed',[
            'help' => 'Seeds the database with initial records',
            'arguments' => [
                'file' => ['help'=>'seed or Plugin.seed']
            ]
        ]);
        $this->addCommand('reset',[
            'help' => 'Drops the database and then runs setup'
        ]);
        $this->addCommand('setup',[
            'help' => 'Creates the database,loads schema and seeds the database'
        ]);

        
    }

    /**
     * Creates the database
     *
     * @return void
     */
    public function create()
    {
        $datasource = $this->getDatasource();
        if( $this->Db->create($datasource)){
            return $this->status('ok','Database created');
        }
        return $this->status('error','Error creating database');
    }

    public function setup(){
        $this->create();
        $datasource = $this->getDatasource();
        $this->schemaLoad('schema',$datasource);
        $this->seed();
    }

    public function reset(){
        $this->drop();
        $this->setup();
    }

     /**
     * Creates the database
     *
     * @return void
     */
    public function drop()
    {
        $datasource = $this->getDatasource();
        if( $this->Db->drop($datasource)){
            return $this->status('ok','Database dropped');
        }
        return $this->status('error','Error dropped database');
    }

    /**
     * Runs the config/db/seed.sql
     *
     * @return void
     */
    public function seed(){
        $datasource = $this->getDatasource();
        $name = 'seed';
        if($this->args(0)){
            $name = $this->args(0);   
        }
        if($this->Db->hasSQLFile($name)){
            if( $this->Db->load($datasource,$name)){
                return $this->status('ok','Database seeded');
            }
            return $this->status('error','Database not seeded');
        }
        $this->status('skipped',"{$name} not found");
    }

   
    /**
     * handles the dumping and loading of schema
     *
     * @return void
     */
    public function schema() 
    {
        $datasource = $this->getDatasource();

        // Handle Dumping
        if($this->args(0) === 'dump'){
           if( $this->Db->dump($datasource)){
               return $this->status('ok','Schema dumped');
           }
           return $this->status('error','Error dumping schema');
        }
        // Handle loading
        if($this->args(0) === 'load'){
            $name = 'schema';
            if($this->args(1)){
                $name = $this->args(1);   
            }
            return $this->schemaLoad($name,$datasource);
         }
         $this->error('Unkown action ' . $this->args(0));
    }

    protected function schemaLoad($name,$datasource){
        if($this->Db->hasSQLFile($name)){
            if( $this->Db->load($datasource,$name)){
                return $this->status('ok','Schema loaded');
            }
            return $this->status('error','Schema not loaded');
        }
        $this->status('skipped',"{$name} not found");
    }

    protected function getDatasource(): string
    {
        $datasource = 'default';
        if (!empty($this->params('datasource'))) {
            $datasource = $this->params('datasource');
        }
        return $datasource;
    }
}