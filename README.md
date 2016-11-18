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
