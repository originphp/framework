# Console & Shell

It is easy to build console applications, use can be for cron jobs, running complex calculations or anything data intensive.  Console apps are run though PHP cli.

## Building a Shell

Shells are stored in the `Console` folder of your app. Here is an example of a simple shell

```php
namespace App\Console;

use Origin\Console\Shell;

class HelloShell extends Shell
{
    public function run()
    {
        return $this->out('Hello world!'); // Outputs to screen
    }
}
```

From within your project folder

```linux
$ bin/console hello run
```

You can also set a `main` method, which will be run by default if no command is supplied.

```php
 public function main()
    {
        $this->out('hello world!');
    }
```
Then to run it, do so as follows:

```linux
$ bin/console hello
```

When using the `main` method, it cannot accept arguments, since the shell will think you are calling a command within the shell and main wont get executed. However `main` does accept `options`.

## Arguments

You can get additional arguments by checking out the args variable in the shell.

```php
print_r($this->args()); // or use pr() 
```

## Display Help

To display help for your commands, this is displayed when no arguments are called from the command line, configure this
in the initialize method.


```php
 public function initialize()
    {
        $this->addCommand('purge', ['help'=>'Purges the temporary files']);
    }
```

## Option parsing

Sometimes you need to accept parameters from your console.

Lets say you wanted to accept a dryRun option.

```linux
$ bin/console database clean --dryRun
```

To do so you need to configure this in your initialize method, at the same time you put the help text which will be displayed
when the shell is called with no arguments.

```php
    public function initialize()
    {
        $this->addOption('dryRun', [
            'help'=>'To simulate it being run no data is modified'
            ]);
    }
```

Which then can be accessed like this, these type of options will set the param as true.

```php
if($this->params('dryRun')){
    ..
}
```

Sometimes you might want to give a short name which is then accessed with a single dash , for example `-d`

```php
    public function initialize()
    {
        $this->addOption('dryRun', [
            'help'=>'To simulate it being run no data is modified','short'=>'d'
            ]);
    }
```

If you need to take an input from the user then it will be done like this from the console.

```linux
$ bin/console database clean --datasource=test_database
```

To configure this, you set a value option with a slight description, this will appear in the help.

```php
    public function initialize()
    {
        $this->addOption('datasource', [
            'help'=>'Name of the connection use','value'=>'name'
            ]);
    }
```


```php
$connection = $this->params('datasource');
```

## Displaying Help

When you create a console, in the initialize setup the commands which people can run this will then generate help
when it is asked. 

Note: If no commands are setup in the shell using `addCommand`, then any public method can be called. However, if you setup commands, then only those commands can be called.

```php
    public function initialize()
    {
        $this->addCommand('generate', ['help'=>'Generates the config\schema\\table.php file or file']);
        $this->addCommand('create', ['help'=>'Creates the tables using the schema .php file or files']);
        $this->addCommand('import', ['help'=>'Imports raw SQL from file or files']);
        $this->addOption('datasource', ['help'=>'Use a different datasource','value'=>'name','short'=>'ds']);
    }
```

## Callbacks

There are three callbacks which a Shell use `initialize`,`startup` and `shutdown`;

```php
    /**
     * This is called when the shell is created during the construct.
     */
    public function initialize(){}

    /**
     * This called before the shell method
     */
    public function startup(){}

    /**
     * This is called after the shell method
     */
    public function shutdown(){}
```

## Methods

### out

This outputs text to the console

```php
$this->out('Hello world!');
```

### in

The in method prompts the user for input, you can also supply a default option if they press return (without entering anything).

```php
$answer = $this->in('Do you want to continue', ['yes','no'], 'yes');
```

This will output:

```
Do you want to continue? (yes/no) [yes]
```

## Shell Tasks

Shells also have tasks, similar to components which are used by controllers. Tasks allow you to share functionality between your shells.

For more information see the [Tasks Guide](tasks.md).


## Running Shells as Cron Jobs

Many applications will need to run cron jobs on scripts, these can be to clean the database, send out emails, carry out tasks etc. You can run your shell scripts through cron by editing the cron file.

On Ubunu or other Debian based flavors of unix use the crontab command.

````linux
    sudo crontab -u www-data -e
````

For Redhat or Redhat base distributions edit the `/etc/crontab` file, although at the time of writing Redhat does not officially support Php 7.0.

To setup a cron to run the send_emails method in the users shell once each day

````
0 1 * * * cd /var/www/project && bin/console users send_emails
````

For help with cron schedule expressions, see [cron guru](https://crontab.guru).