<?php
namespace Origin\Command;

class ElasticsearchIndexCommand extends Command
{
    protected $name = 'elasticsearch:index';
    protected $description = 'Deletes and then recreates indexes and adds data to the indexes from the database';
    protected $help = 'Deletes existing indexes, then creates the new one with the settings defined in the model, then imports the data into the index.';

    public function initialize()
    {
        $this->addArgument('model', ['type' => 'array','required' => true,'description' => 'Model or list of models seperated by spaces']);
    }
 
    public function execute()
    {
        $models = $this->arguments('model');
        foreach ($models as $model) {
            $this->loadModel($model);

            if (isset($this->{$model}->ElasticSearch)) {
                $this->{$model}->deleteIndex();
                $this->{$model}->createIndex();
                $count = $this->{$model}->import();
                $this->io->status('ok', "{$model} index created and {$count} record(s) added to index");
            } else {
                $this->io->status('skipped', "{$model} does not have Elasticsearch Behavior loaded");
            }
        }
    }
}
