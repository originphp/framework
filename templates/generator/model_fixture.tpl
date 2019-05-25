<?php 
namespace %namespace%\Test\Fixture;

use Origin\TestSuite\Fixture;

class %class%Fixture extends Fixture
{
    // for initial development
    public $import = ['model' =>'%class%'];

    // set once database structure is finalized
    public $schema = [];

    public $records = [];

}