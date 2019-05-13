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

 /**
  * This will be depreciated. This is more helper as it is for output.
  */
namespace Origin\Console\Task;

use Origin\Model\Model;
use Origin\Migration\Adapter\MysqlAdapter;
use Origin\Exception\Exception; 

class MigrationTask extends Task
{
    const PATH = ROOT . '/db/migrate';

    public function initialize(array $config)
    {
        $this->Migration = new Model(['name'=>'Migration']); // Create a dynamic model
        $this->Migration->loadBehavior('Timestamp');
    }

    /**
     * Gets the last migration version
     *
     * @return int|null
     */
    public function lastMigration(){
       $lastMigration = $this->Migration->find('first', ['order'=>'version DESC']);
       if($lastMigration){
           return $lastMigration->version;
       }
       return null;
    }

    public function rollback(string $version){

        $migrations = $this->getMigrations($version,$this->lastMigration());
        $migrations = array_reverse($migrations);
     
        if(empty($migrations)){
            $this->out('<comment>No migrations found</comment>');
            return;
        }
        $start = time();
        foreach ($migrations as $object) {
  
            $this->out("<red>{$object->name}</red> [<yellow>{$object->version}</yellow>]");
            try {
                $migration = $this->createMigration($object);
                $entity = $this->Migration->find('first',['conditions'=>['version'=>$object->version]]);
                /**
                 * Do the magic
                 */
                $reverse = [];
                if($entity->rollback){
                    $reverse = json_decode($entity->rollback,true);
                }
                $this->verboseStatements($migration->rollback($reverse));
              
                $this->Migration->delete($entity);          
            }
            catch(Exception $ex) {
                $this->shell()->error($ex->getMessage());
            }
        }

        $this->out(sprintf('Rollback complete. Took <white>%d</white> seconds', (time() - $start)));
    }

    private function createMigration(object $object){
        include self::PATH . DIRECTORY_SEPARATOR . $object->filename;
           
        $migration = new $object->class(new MysqlAdapter(), [
            'datasource' => $this->config('datasource'),
            'version' => $object->version,
            'name' => $object->name
        ]);
        return $migration;
    }

    public function migrate(string $version = null){
        $migrations = $this->getMigrations($this->lastMigration(),$version);
        if(empty($migrations)){
            $this->out('<comment>No migrations found</comment>');
            return;
        }
        $start = time();
        foreach ($migrations as $object) {
            $this->out("<notice>{$object->name}</notice> [<yellow>{$object->version}</yellow>]");
            try {
                $migration = $this->createMigration($object);

                $this->verboseStatements($migration->start());
               // pr($migration->reverseStatements());
                $entity = $this->Migration->new([
                    'version' => $object->version,
                    'rollback' => json_encode($migration->reverseStatements())
                ]);
         
                $this->Migration->save($entity);  
                
            }
            catch(Exception $ex) {
                $this->shell()->error($ex->getMessage());
            }
        }
        $this->out(sprintf('Migration complete. Took <white>%d</white> seconds', (time() - $start)));
    }

    private function verboseStatements(array $statements){
        $this->out("");
        foreach($statements as $statement){
            $this->out(sprintf("<green> > </green><comment>%s</comment>",$statement));
            $this->out("");
        } 
    }

      /**
     * Returns an array of migrations
     *
     * @param integer $from
     * @param integer $to
     * @return void
     */
    private function getMigrations(int $from = null, int $to = null)
    {
        $results = array_diff(scandir(self::PATH), array('.', '..'));
        $migrations = [];
        foreach ($results as $file) {
            $class = pathinfo($file, PATHINFO_FILENAME);
         
            if (preg_match('/^([0-9]{14})(.*)/', $class, $matches)) {
                $version  = $matches[1];
                if (($from and $version <= $from) or ($to and $version > $to)) {
                    continue;
                }
                $migrations[] = (object) [
                    'name' => $matches[2],
                    'version'=>$matches[1],
                    'class' => $matches[2] .'Migration',
                    'filename'=> $file,
                ];
            }
        }
        return $migrations;
    }

}