# Components

To create a component :

````php

namespace App\Controller\Component;
use Origin\Controller\Component\Component;

class MathComponent extends Component
{
  public function sum($x, $y){
    return $x+$y;
  }
  public function doSomethingWithControler(){
    $controller = $this->controller(); // get current controller
    $result = $controller->method('xyz');
  }
}

````

To load a component

````php
  class WidgetsController extends AppController
  {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->loadComponent('Math');
    }
  }

````
 To use a component

````php
    class WidgetsController extends AppController
    {
      public function doSomething(){
        return $this->Math->sum(1,2);
      }
    }

````
