# Components

Components are a way to share code between your controllers. When you create a component, you can use other components within the component and access the current controller.

## Creating a Component

Create the component file in the `Controller/Component` folder.

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
## Loading Components

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
## Using Components

 To use a component, you call it from within your controller methods.

````php
    class WidgetsController extends AppController
    {
      public function doSomething(){
        return $this->Math->sum(1,2);
      }
    }

````

If you want to use a component within a component then in the component class add the components that you
want to use to the `uses` array.

````php

class MathComponent extends Component
{
  public $uses = ['Fractions'];
}

````

## Callbacks 

There are three callbacks which Components use `initialize`,`startup` and `shutdown`;

````php
    /**
     * This is called when component is created for the first time from the
     * controller.
     */
    public function initialize(array $config){}

    /**
     * This called after the controller startup but before the controller action.
     */
    public function startup(){}

    /**
     * This is called after the controller action.
     */
    public function shutdown(){}
````
## Properties

### array Component::$uses

This is an array of other components that you component needs, any components listed here will be loaded automatically when your component is created.

## Methods

### Component::controller()

This returns the controller that loaded the component. This is useful if you need to do something with a controller from within your component.