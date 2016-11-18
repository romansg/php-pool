# php-pool
A pool to store jobs and tasks to be processed later in the background.

## Description

**php-pool** implements a pool of tasks and jobs with a database backend. A pool is not a queue but merely a repository where jobs and its related tasks can be stored for later retrieval and processing by one or more workers running in the background.

Jobs and tasks can be anything you need; strings, arrays, objects. php-pool is agnostic of their nature.

The main goal of **php-pool** is to run lengthy processes in the background without blocking the user interface.

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

```php
$total = count($tasks);

$bgprocess->execute($jobId, $total/2);
$bgprocess->execute($jobId, $total/2);
````

The first worker will perform half of the pending tasks and the second one will perform the other half because the first half is already reserved.

You can split task processing in as many parts as you want or as system resources permit. To facilitate the splitting use a broker:

```php
$broker = new \Pool\Broker($bgprocess, $parts);
$broker->execute($jobId, count($tasks), $parts);
```

The broker will start $parts number of processes dividing count($tasks) homogeneously.

### The worker script

The worker script instantiates and execute a suitable worker object, that is, an instance of a suitable \Pool\Worker derived class:

```php
// Read command line arguments
$jobId = isset($argv[1]) ? $argv[1] : 0;
$count = isset($argv[2]) ? $argv[2] : -1; // -1 means process all tasks

// Do some validation
if (!is_int($jobId) || $jobId <= 0) {
	die("Missing or invalid parameter: jobId\n");
}

if (!is_int($count) {
	die("Invalid parameter: count\n");
}

if ($count == 0) {
	exit("Nothing to do\n");
}

// Connect to the pool
$manager = new \Pool\Manager($dsn, 'someuser', 'somepassword');

// Execute worker
$worker = new CustomWorker($manager);
$worker->execute($jobId, $count);
```

CustomWorker is implemented by you to perform your specific job and tasks.

### The worker object

A CustomWorker implements \Pool\Worker::initialize() and \Pool\Worker::perform() methods:

```php
class CustomWorker extends \Pool\Worker
{
	protected function initialize($job) {
		//
		// Do whatever initialization you need
		//
	}
	
	protected function perform($task) {
		//
		// Called to perform each task
		//
	}
}
```

So, here is where the real job is done. The $job and $task parameters are in the exact format they were originally stored in the pool. Therefore, if they correspond to objects, their class definitions must have been already loaded.

Be aware that the initialization method is called whenever the worker is instantiated and thus, it can be invoked more than once if you split the task processing into several workers.

Also notice that the perform method must return true on sucess and false otherwise.

## Some Details

Here we will explain some details about the pool and its database backend.
