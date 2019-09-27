<?php
namespace %namespace%\Test\Model\Behavior;

use Origin\TestSuite\OriginTestCase;
use Origin\Model\Model;
use Origin\Model\ModelRegistry;
use %namespace%\Model\Behavior\%class%Behavior;

// Fake model - You can use any 
class DummyModel extends Model
{
}

class %class%BehaviorTest extends OriginTestCase
{
    /**
    * Use fixture to import data
    *
    * @var array
    */
    public $fixtures = [];

    /**
    * @var \App\Model\Behavior\%class%Behavior
    */
    protected $%class% = null;

    public function startup() : void
    {
         $model = ModelRegistry::get('DummyModel', [
            'className' => DummyModel::class,
        ]);

        $behaviorConfig = [];

        $this->%class% = new %class%Behavior($model,$behaviorConfig);
    }
}