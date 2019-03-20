# Behaviors

A behavior is way to add functionality to you models and then share this with other models, similar to controller components.
Behaviors have the word behavior added on the end of the class and file name.

To create a behavior you will need to create a file in `src/Model/Behavior` folder and call it `FooBehavior`.

```php

namespace App\Model\Behavior;
use Origin\Model\Behavior\Behavior;

class FooBehavior extends Behavior
{
  public function doSomething(){
    return true;
  }
}

```

To load the behavior, call `loadBehavior` from the `intialize` method.

```php
    class Article extends AppModel
    {
      public function initialize(array $config)
      {
          parent::initialize($config);
          $this->loadBehavior('Foo');
      }
    }

```

To use a behavior the functions will be added to the model.

```php
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
```

To access a model from within a behavior

```php
class WidgetBehavior extends Behavior
{
    public function doSomething(){
        $widgets = $this->model()->find('all');
    }
}
```

Behaviors have the same [callbacks](models-callbacks.md) functions as models. So just add the callbacks that you need.

Sometimes you will need to disable or unload behaviors, to do this you will need access the behavior registry

```php
    class Article extends AppModel
    {
      public function import()
      {
          $this->disableBehavior('timestamp');

          ...

          $this->enableBehavior('timestamp');

      }
    }
```
