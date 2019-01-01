# Console

It is easy to build console applications, use can be for cron jobs, running complex calculations or anything data intensive.

## Building a shell app


Shells are stored in the `Console` folder of your app. Here is an example of a simple shell

````php
namespace App\Console;

use Origin\Console\Shell;

class HelloShell extends Shell
{
    /**
     * This is called when the Shell is created. You can put any logic here
     * instead of overiding the construct.
     */ 
    public function initialize(array $arguments){
        ...
    }

    /**
     * This will be called if no other args are put after the shell name
     */
    public function main(){

    }

    public function world()
    {
        return $this->out('Hello world!'); // Outputs to screen
    }

}
````
From within your project directory type `bin/console hello` to run the main method
or `bin/console hello world` run the world method.

You can get additional arguments by checking out the args variable in the shell.

`print_r($this->args)`

## Callbacks

Like Components, Shell apps have initialze, startup and shutdown