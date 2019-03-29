# Queue for Background Jobs

The queue system is for handling background jobs, such as sending emails, carrying out database maintenance and so on. The back-end for queue system is MySQL, and you can use your current database or separate server. 

First you need to import the SQL schema which is in the `config/schema/queue.sql`

Run the following command:

```linux
bin/console schema import queue
```

## Creating the Queue Object

To create a Queue object which uses the default database connection.

```php
Use Origin\Utility\Queue;
$queue = new Queue();
```

If you want to use a different datasource and/or table then you can configure this by passing an array of
options. These options are the same as when creating a model.

```php
Use Origin\Utility\Queue;
$queue = new Queue([
    'datasource'=>'queue-server',
    'table'=>'jobs'
    ]);
```

## Adding Jobs to the Queue

You will most likely be adding jobs to a queue from either the controller, model or shell. 

To add a job to the queue you just use set a queue name and then pass array of data. The queue name can consist of letters,numbers,hyphens and underscores. Once you have added the job it will return the job id.

```php
Use Origin\Utility\Queue;
$queue = new Queue();
$jobId = $queue->add('welcome_emails',[
    'user_id'=>1024
    ]);
```

You can also delay (or schedule) the job using a `strtotime` compatible string including a `Y-m-d H:i:s` date.

```php
 $queue->add('welcome_emails',[
    'user_id'=>1024
    ],
    '+5 hours');
```

## Fetching Jobs from the Queue

The fetch method pulls one job at a time and locks it to prevent the job from being run more than once. For example, this can happen when running a cron job every minute, and the first job takes more than a minute, we lock it so even two processes that are running at the same time, so only one of them will get the job.

If there is a job in the queue then it will return a Job object. When you process a job, it is best to run through a try block, in-case of any unexpected errors or exceptions that might happen.

```php
 Use Origin\Utility\Queue;
 $queue = new Queue();

 while ($job = $queue->fetch('welcome_emails')) {
    try {
        $message = $job->data();
        $this->sendWelcomeEmail($message->user_id);
        $job->executed();
    } catch (Exception $e) {
        $job->failed();
        $logger->error('Job with id {id} failed.',['id'=>$job->id]);
    }  
 }
```

## Processing Jobs

Jobs in the queue are typically processed in the background, and this will usually be done in a shell script

```php
<?php

namespace App\Console;

use App\Console\AppShell;

class UsersShell extends AppShell
{
    public function hourly(){
        $this->sendWelcomeEmails();
    }
    protected function sendWelcomeEmails(){

        while ($job = $queue->fetch('welcome_emails')) {
            ...
        }
    }
}
```

On Ubuntu to setup cron tab for the `www-data` user type in the following command:

```linux
$ sudo crontab -u www-data -e
```

Then add the following line, assuming the source code is in the folder
`/var/www/app.mydomain.com`.
```
0 * * * * cd /var/www/app.mydomain.com && bin/console users hourly
```

## Accessing The Model

If you need to do something with records in the queue table you can access the job model calling the `model` method.

```php
$Job = $queue->model();
$jobs = $Job->find('all');
```

## Purging Jobs

To purge all the executed jobs from the database

```php
$queue->purge();
```

Or to just purge a specific a queue, use the queue name

```php
$queue->purge('welcome-emails');
```