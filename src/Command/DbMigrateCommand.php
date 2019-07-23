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

use Origin\Model\Model;
use Origin\Exception\Exception;

class DbMigrateCommand extends Command
{
    protected $name = 'db:migrate';

    protected $description = 'Runs and rolls back migrations';

    const PATH = APP . DS . 'db'. DS .'migrate';

    public function initialize()
    {
        $this->addOption('datasource', [
            'description' => 'Use a different datasource','short' => 'ds','default' => 'default',
        ]);
        $this->addArgument('version', [
            'description' => 'a target version e.g. 20190511111934',
        ]);
    }
 
    public function execute()
    {
        $version = $this->arguments('version');

        # Dynamically Create Migration Model for CRUD
        $this->Migration = new Model([
            'name' => 'Migration',
            'datasource' => $this->options('datasource'),
        ]);
        $this->Migration->loadBehavior('Timestamp');

        $lastMigration = $this->lastMigration();
        if ($version === null or $version > $lastMigration) {
            $this->migrate($version);
        } else {
            $this->rollback($version);
        }
    }

    protected function migrate(string $version = null)
    {
        $migrations = $this->getMigrations($this->lastMigration(), $version);
        if (empty($migrations)) {
            $this->io->warning('No migrations found');

            return;
        }
        $start = microtime(true);
        
        $count = 0;
        foreach ($migrations as $object) {
            $this->out("<notice>{$object->name}</notice> [<yellow>{$object->version}</yellow>]");
            try {
                $migration = $this->createMigration($object);

                $this->verboseStatements($migration->start());
                // pr($migration->reverseStatements());
                $entity = $this->Migration->new([
                    'version' => $object->version,
                    'rollback' => json_encode($migration->reverseStatements()),
                ]);
         
                $this->Migration->save($entity);
                $count++;
            } catch (Exception $ex) {
                $this->throwError($ex->getMessage());
            }
        }

        $this->io->success(sprintf('Migration Complete. %d migrations in %d ms', $count, (microtime(true) - $start)));
    }

    protected function rollback(string $version)
    {
        $migrations = $this->getMigrations($version, $this->lastMigration());
        $migrations = array_reverse($migrations);
  
        if (empty($migrations)) {
            $this->io->warning('No migrations found');

            return;
        }
        $count = 0;
        $start = microtime(true);
        foreach ($migrations as $object) {
            $this->out("<red>{$object->name}</red> [<yellow>{$object->version}</yellow>]");
            try {
                $migration = $this->createMigration($object);
                $entity = $this->Migration->find('first', ['conditions' => ['version' => $object->version]]);
                /**
                 * Do the magic
                 */
                $reverse = [];
                if ($entity->rollback) {
                    $reverse = json_decode($entity->rollback, true);
                }
                $this->verboseStatements($migration->rollback($reverse));
              
                $this->Migration->delete($entity);
                $count++;
            } catch (Exception $ex) {
                $this->throwError($ex->getMessage());
            }
        }
        $this->io->success(sprintf('Rollback Complete. %d migrations in %d ms', $count, (microtime(true) - $start)));
    }

    /**
     * Creates the object
     *
     * @param object $object
     * @return void
     */
    private function createMigration(object $object)
    {
        include_once self::PATH . DIRECTORY_SEPARATOR . $object->filename;
        $adapter = $this->Migration->connection()->adapter();
        $migration = new $object->class($adapter);

        return $migration;
    }

    /**
     * Gets the last migration version
     *
     * @return int|null
     */
    private function lastMigration()
    {
        $lastMigration = $this->Migration->find('first', ['order' => 'version DESC']);
        if ($lastMigration) {
            return $lastMigration->version;
        }

        return null;
    }

    private function verboseStatements(array $statements)
    {
        $this->out('');
        foreach ($statements as $statement) {
            $this->out(sprintf('<green> > </green><text>%s</text>', $statement));
            $this->out('');
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
        $results = array_diff(scandir(self::PATH), ['.', '..']);
        $migrations = [];
        foreach ($results as $file) {
            $class = pathinfo($file, PATHINFO_FILENAME);
         
            if (preg_match('/^([0-9]{14})(.*)/', $class, $matches)) {
                $version = $matches[1];
                if (($from and $version <= $from) or ($to and $version > $to)) {
                    continue;
                }
                $migrations[] = (object) [
                    'name' => $matches[2],
                    'version' => $matches[1],
                    'class' => $matches[2] .'Migration',
                    'filename' => $file,
                ];
            }
        }

        return $migrations;
    }
}
