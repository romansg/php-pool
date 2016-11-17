<?php
namespace Pool;

/**
 * Handles the execution of a worker script by splitting it into several processes that run in the background.
 *
 * A worker script should be a php script that instantiate a worker object {@see Pool\Worker} to perform a number of tasks
 * from the pool. This worker script should accept two arguments: the tasks' job's id and the number of pending tasks to be
 * performed. Actually, those are the parameters to be passed to worker object's execute method {@see Pool\Worker::execute}
 * and finally to the manager's collectTasks method {@see Pool\Manager::collectTasks)
 */
class Broker
{
	/**
	 * Constructor.
	 * 
	 * @param BgProcess $bgProcess {@see Pool\BgProcess)
	 */
	public function __construct(BgProcess $bgProcess) {
		$this->bgProcess = $bgProcess;
	}

	/**
	 * Executes the process as many times as specified
	 *
	 * @param integer $jobId Job's id
	 * @param integer $count Total number of pending tasks to be executed (-1 means all tasks)
	 * @param integer $parts Number of processes to be run
	 * @return void
	 */
	public function execute($jobId, $count = -1, $parts = 1) {
		$slices = array_fill(0, $parts, floor($count / $parts));

		for ($i = 0; $i < ($count % $parts); $i++) {
			$slices[$i]++;
		}

		foreach ($slices as $count) {
			$this->bgProcess->execute($jobId, $count);
		}
	}
}
