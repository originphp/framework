<?php
use Origin\Migration\Migration;

class CreateFooTableMigration extends Migration
{
    public function change() : void
    {
        $this->createTable('foo', [
            'name' => 'string',
            'description' => 'text',
        ]);
    }
}
