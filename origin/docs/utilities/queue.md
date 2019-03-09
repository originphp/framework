# Queue for Background Jobs

The queue system is for handling background jobs, such as sending emails, carrying out database maintenance and so on. The back-end for queue system is MySQL, and you can use your current database or separate server. 

First you need to import the SQL schema which is in the `config/schema/queue.sql`

Run the following command:

````linux
bin/console schema import queue
````

## Creating the Queue Object

To create a Queue object which uses the default database connection.

````php
Use Origin\Utils\Queue;
$queue = new Queue();
````

If you want to use a different datasource and/or table then you can configure this by passing an array of
options. These options are the same as when creating a model.

````php
Use Origin\Utils\Queue;
$queue = new Queue([
    'datasource'=>'queue-server',
    'table'=>'jobs'
    ]);
````

## Adding Jobs to the Queue

To add a job to the queue, you just use set a queue name and then pass array of data. This data is stored
as a JSON string in the table. The queue name can consist of letters,numbers,hypens and underscores. Once you have added the job it will return the job id.

````php
Use Origin\Utils\Queue;
$queue = new Queue();
$jobId = $queue->add('welcome_emails',[
    'user_id'=>1024
    ]);
````

You can also delay (or schedule) the job using a `strtotime` compatible string or `Y-m-d H:i:s` date.

````php
 $queue->add('welcome_emails',[
    'user_id'=>1024
    ],
    '+5 hours');
````

## Fetching Jobs

The fetch method pulls one job at a time and locks it to prevent the job from being run more than once. For example, this can happen when running a cron job every minute, and the first job takes more than a minute, we lock it so even two processes that are running at the same time, so only of them will get the job.

````php
 while ($job = $queue->fetch('welcome_emails')) {
    ...
 }
````

If there is a job in the queue then it will return a job object. The body is the message you passed when you added the job to the queue, this is also returned as an object as it is decoded from the JSON string.

When you process a job, it is best to run through a try block, incase of any unexpected errors or exceptions that might happen. 

````php
 Use Origin\Utils\Queue;
 $queue = new Queue();

 while ($job = $queue->fetch('welcome_emails')) {
    try {
        $message = $job->getBody();
        $this->User->sendEmail($message->user_id);
        $job->delete();
    } catch (Exception $e) {
        $job->failed();
    }  
 }
````

If you want to keep track of the jobs that have been processed then use executed, which will set the status accordingly.

````php
 Use Origin\Utils\Queue;
 $queue = new Queue();

 while ($job = $queue->fetch('welcome_emails')) {
    try {
        $message = $job->getBody();
        $this->User->sendEmail($message->user_id);
        $job->executed();
    } catch (Exception $e) {
        $job->failed();
    }  
 }
````

You can also set custom statuses:

````php
    $job->status('skipped');
````

When you set a status (including executed or failed), the job is released (unlocked), this is important incase of long running jobs or timeouts which might cause stuck jobs, and will allow you to identify these in the database because they will be locked.

## Accessing The Model

If you need to do something with the queue table you can access the job model calling the `model` method.

````php
$Job = $queue->model();
$jobs = $Job->find('all');
````


## Stuck Jobs

You can access stuck jobs using the stuck method and pass a valid strtotime compatible string.

````php
$stuck = $queue->stuck('-1 minutes');
````