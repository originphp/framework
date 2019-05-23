<?php
use Origin\Migration\Migration;

class CreateBarTableMigration extends Migration
{
    public function change()
    {
        $this->createTable('bar',[
            'name' => 'string',
            'description' => 'text'
        ]);
    }
}