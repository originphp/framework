<?php
use Origin\Migration\Migration;

class CreateFooBarTableMigration extends Migration
{
    public function change() : void
    {
        $this->createTable('foobar', [
            'name' => 'string',
            'description' => 'text',
        ]);
    }
}
