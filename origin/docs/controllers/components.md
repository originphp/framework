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

If you want to use a component within a component then call the `loadComponent` method.

````php

class MathComponent extends Component
{
   public function initialize(array $config)
    {
      $this->loadComponent('Math',$config);
    }
}

````
or use the `components` method to load many components.

````php

class MathComponent extends Component
{
   public function initialize(array $config)
    {
      $this->loadComponents(['Math']);
    }
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
     * This is called after the controller action but before the controller shutdown
     */
    public function shutdown(){}
````

## Methods

### Component::controller()

This returns the controller that loaded the component. This is useful if you need to do something with a controller from within your component.


### Component::loadComponent(string $name,array $config=[])

Tells the lazy loader that you will be using another Component within this component. Once you do this you can
access the component using `$this->AnotherComponent->method()`; 

### Component::components(array $names)

Loads multiple components using the `loadComponent` method.