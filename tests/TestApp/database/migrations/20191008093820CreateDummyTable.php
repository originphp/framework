<?php
use Origin\Migration\Migration;

class CreateDummyTableMigration extends Migration
{
    public function change() : void
    {
        $this->createTable('dummies',[
  'name' => 'string',
  'description' => 'text',
]);
    }
}