# Behaviors

To create a behavior :

````php

namespace App\Model\Behavior;
use Origin\Model\Behavior\Behavior;

class FooBehavior extends Behavior
{
  public function doSomething(){
    return true;
  }
}

````

To load a behavior

````php
    class Article extends AppModel
    {
      public function initialize(array $config)
      {
          parent::initialize($config);
          $this->loadBehavior('Foo');
      }
    }

````

To use a behavior the functions will be added to the model.

````php
    class Article extends AppModel
    {
      public function demo()
      {
          if($this->doSomething()){
            return true;
          }
          return false;
      }
    }

````

Behaviors have the same callbacks functions as models. So if you add them they will be called.
