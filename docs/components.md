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
## Loading Components

To load a component from the controller, you should place this in the initialize method so the the callbacks can be executed.

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

If you want to use a component within a component then you call the `loadComponent` method, the component will then be lazy loaded when you next call it.

```php

class MathComponent extends Component
{
   public function initialize(array $config)
    {
      $this->loadComponent('Math',$config);
    }
}

```
or use the `components` method to load many components.

```php

class MathComponent extends Component
{
   public function initialize(array $config)
    {
      $this->loadComponents(['Math']);
    }
}

```

## Initialize

After a component is created the `initialize` method will be called, this is where you can put any code
that you need to be executed when a component is created. This is a hook so you don't need to override the `___construct()`.

## Callbacks 

There are two callbacks which Components use `startup` and `shutdown`. To use the callbacks, just create a method in your component with the callback name.

### Startup callback
This called after the controller `beforeFilter` but before the controller action.

```php
    public function startup(){}
```

### Stutdown callback
This is called after the controller action but before the controller `afterFilter`.

```php
    public function sthudown(){}
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

When working with components you will occasionally need to access the controller, this can be easily done by calling the controller method.

```php
$controller = $this->controller();
```