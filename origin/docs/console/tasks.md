# Tasks

Tasks are a way to share code between your shells. When you create a task, you can use other tasks within the task and access the current shell.

## Creating a Task

Create the task file in the `Console\Task` folder.

```php

namespace App\Console\Task;
use Origin\Console\Task;

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

## Loading Tasks

To load a task

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

If you want to use a task within a task then call the `loadTask` method.

```php

class MathTask extends Task
{
   public function initialize(array $config)
    {
      $this->loadTask('Math',$config);
    }
}

```

or use the `loadTasks` method to load many

```php

class MathTask extends Task
{
   public function initialize(array $config)
    {
      $this->loadTasks(['Math']);
    }
}

```

## Callbacks

There are three callbacks which Tasks use `initialize`,`startup` and `shutdown`;

```php

    /**
     * This is called when task is loaded for the first time
     */
    public function initialize(array $config){}

    /**
     * This called after the shell startup but before the shell method.
     */
    public function startup(){}

    /**
     * This is called after the shell method but before the shell shutdown
     */
    public function shutdown(){}
```

## Methods

### shell

This returns the controller that loaded the task. This is useful if you need to do something with a controller from within your task.

### loadTask

Tells the lazy loader that you will be using another Task within this task. Once you do this you can
access the task using `$this->AnotherTask->method()`.