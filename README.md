# php-pool
A pool to store jobs and tasks to be processed later in the background.

## Description

**php-pool** implements a pool of tasks and jobs with a database backend. A pool is not a queue but merely a repository where jobs can be stored for later retrieval by a worker that can be run in the background.

Jobs and tasks can be added via a web interface and a worker can be started in the background to perform tasks and thus releasing the web interface, which is convenient for lengthy processes.

## Basic use

### Adding tasks

```php
$manager = new \Pool\Manager($dsn, 'someuser', 'somepassword');

$jobId = $manager->addJob($job);

foreach ($tasks as $task) {
	$manager->addTask($jobId, $task);
}
```

Here, $job and $task(s) could be anything you like; strings, objects, anything that fit the master-detail relation: jobs are made out of tasks.

### Start a worker script in the background

```php
$bgprocess = new \Pool\BgProcess('/usr/local/bin/php worker.php');
$bgprocess->execute($jobId, count($tasks));
```

$bgprocess is instantiated with a call to the php executable passing the worker script as an argument. The execute method receives the script's arguments, in this case, the job's id and the number of tasks to be processed. The worker script should default the second argument to all tasks, so we could simply code:

```php
$bgprocess->execute($jobId);
```

if we want to perform all job's tasks in a row.

### Using a broker to split the process

The second argument passed to the worker script allows you to split the process into several threads to have the work done faster:

´´´php
$total = count($tasks);

$bgprocess->execute($jobId, $total/2);
$bgprocess->execute($jobId, $total/2);
´´´

The first worker will perform half of the pending tasks and the second one will perform the other half because the first half is already reserved.

You can split task processing in as many parts as you want or as system resources permit. To facilitate the splitting use a broker:

´´´php
$broker = new \Pool\Broker($bgprocess, $parts);
$broker->execute($jobId, count($tasks), $parts);
´´´

The broker will start $parts number of processes dividing count($tasks) homogeneously.
