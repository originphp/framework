<?php
use Origin\Migration\Migration;

class CreateBarTableMigration extends Migration
{
    public function change() : void
    {
        $this->createTable('bar', [
            'name' => 'string',
            'description' => 'text',
        ]);
    }
}
