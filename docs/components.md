# Components

Components are a way to share code between your controllers. When you create a component, you can use other components within the component and access the current controller.

## Creating a Component

Create the component file in the `Controller/Component` folder.

```php

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
```

After a component is created the component `initialize` method will be called, this is where you can put any code
that you need to be executed when a component is created. This is a hook so you don't need to override the `___construct()`.

## Loading Components

To load a component in the controller, you call `loadComponent` from within the `initialize` method so the the callbacks can be executed.

```php
  class WidgetsController extends AppController
  {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->loadComponent('Math');
    }
  }

```

## Using Components

 To use a component, you call it from within your controller methods.

```php
    class WidgetsController extends AppController
    {
      public function doSomething(){
        return $this->Math->sum(1,2);
      }
    }

```

If you want to use a component within a component then you call the `loadComponent` method, the component will then be lazy loaded when you next call it. When you load a component within a component, this component will not have callbacks executed unless the component is already loaded in a controller.

```php
class MathComponent extends Component
{
   public function initialize(array $config)
    {
      $this->loadComponent('Math',$config);
    }
}
```



## Callbacks

There are two callbacks which Components use `startup` and `shutdown`. To use the callbacks, just create a method in your component with the callback name.

### Startup callback

This called after the controller `beforeFilter` but before the controller action.

```php
    public function startup(){}
```

### Shutdown callback

This is called after the controller action but before the controller `afterFilter`.

```php
    public function stutdown(){}
```

## Accessing the request object

If you need to access the request object from within the component.

```php
$request = $this->request();
```

## Accessing the response object

If you need to access the response object from within the component.

```php
$response = $this->response();
```

## Accessing the controller

When working with components, you may need to access the controller, this can be easily done by calling the controller method.

```php
$controller = $this->controller();
```

## Component Config

Components work with the `ConfigTrait`, so this means you that your component can have its own standardized configuration.


To get a value from the config:

```php
 $value = $this->config('foo'); // bar
```

To all of the values from the config

```php
 $array = $this->config();
```

To set a value in the config:

```php
 $this->config('foo','bar');
 $this->config(['foo'=>'bar']);
```

To set multiple values (not replace the array with the one passed)

```php
 $this->config(['foo'=>'bar']);
```

If you need your component to have a default configuration, then you can set the `$defaultConfig` array property, this will be merged with any config passed when loading a component.

```php
class Foocomponent extends component
{
    protected $defaultConfig = [
        'foo' => 'bar'
    ];
}
```