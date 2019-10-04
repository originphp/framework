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

use Origin\Model\Concern\Elasticsearch;
use Origin\Model\Model;

class ElasticsearchReindexCommand extends Command
{
    protected $name = 'elasticsearch:reindex';
    protected $description = 'Deletes and then recreates indexes and adds data to the indexes from the database';
    protected $help = 'Deletes existing indexes, then creates the new one with the settings defined in the model, then imports the data into the index.';

    public function initialize() : void
    {
        $this->addArgument('model', ['type' => 'array','required' => true,'description' => 'Model or list of models seperated by spaces']);
    }
 
    public function execute() : void
    {
        $models = $this->arguments('model');
        foreach ($models as $model) {
            $this->loadModel($model);

            if ($this->hasConcern($this->$model)) {
                $count = $this->$model->reindex();
                $this->io->status('ok', "{$model} index created and {$count} record(s) added to index");
            } else {
                $this->io->status('skipped', "{$model} does not implement the Elasticsearch Concern");
            }
        }
    }

    private function hasConcern(Model $model) : bool
    {
        $class = get_class($model);
        return in_array(Elasticsearch::class, class_uses($class));
    }
}
