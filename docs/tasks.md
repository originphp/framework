# Tasks

Tasks are a way to share code between your shells. When you create a task, you can use other tasks within the task and access the current shell.

## Creating a Task

Create the task file in the `Console/Task` folder.

```php

namespace App\Console\Task;
use Origin\Console\Task\Task;

class MathTask extends Task
{
  public function sum($x, $y){
    return $x+$y;
  }
  public function doSomethingWithShell(){
    $shell = $this->shell(); // get current shell
    $result = $shell->method('xyz');
  }
}
```

After a task is created the task `initialize` method will be called, this is where you can put any code
that you need to be executed when a task is created. This is a hook so you don't need to override the `___construct()`.

## Loading Tasks

To load a task in the shell, you call `loadTask` from within the `initialize` method so the the callbacks can be executed.

```php
  class WidgetsShell extends AppShell
  {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->loadTask('Math');
    }
  }

```

## Using Tasks

 To use a task, you call it from within your shell methods.

```php
    class WidgetsShell extends AppShell
    {
      public function doSomething(){
        return $this->Math->sum(1,2);
      }
    }

```

If you want to use a task within a task then you call the `loadTask` method, the task will then be lazy loaded when you next call it. When you load a task within a task, this task will not have callbacks executed unless the task is already loaded in a shell.

```php
class MathTask extends Task
{
   public function initialize(array $config)
    {
      $this->loadTask('Math',$config);
    }
}
```

## Callbacks

There are two callbacks which Tasks use `startup` and `shutdown`. To use the callbacks, just create a method in your task with the callback name.

### Startup callback

This called after the shell `beforeFilter` but before the shell action.

```php
    public function startup(){}
```

### Shutdown callback

This is called after the shell action but before the shell `afterFilter`.

```php
    public function stutdown(){}
```
To load a task in the shell, you call `loadTask` from within the `initialize` method so the the callbacks can be executed.

## Accessing the shell

When working with tasks, you may need to access the shell, this can be easily done by calling the shell method.

```php
$shell = $this->shell();
```

## Task Config

Tasks work with the `ConfigTrait`, so this means you that your task can have its own standardized configuration.

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

If you need your task to have a default configuration, then you can set the `$defaultConfig` array property, this will be merged with any config passed when loading a task.

```php
class Footask extends task
{
    protected $defaultConfig = [
        'foo' => 'bar'
    ];
}
```