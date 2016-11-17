<?php
namespace Pool;

/**
 * Handles a job's tasks to be processed
 *
 * This abstract class implements the cycling through tasks to perform them. The actual work must be implemented by derived
 * classes.
 */
abstract class Worker
{
	protected $manager;

	/**
	 * Initializes the job
	 *
	 * @param mixed $job Job's data as stored in the pool
	 * @return void
	 */
	protected abstract function initialize($job);
	
	/**
	 * Performs the given tasks
	 *
	 * @param mixed $task Task's data as stored in the pool
	 * @return void
	 */
	protected abstract function perform($task);

	public function __construct(Manager $manager) {
		$this->manager = $manager;
	}

	/**
	 * Executes given jobs's tasks
	 *
	 * @param integer $jobId Job's id
	 * @param integer $count Number of tasks to be processed (-1 means all tasks)
	 */
	public function execute($jobId, $count = -1) {
		$job = $this->manager->getJob($jobId);
		$tasks = $this->manager->collectTasks($jobId, $count);

		$this->initialize($job);

		foreach ($tasks as $id => $task) {
			if ($this->perform($task)) {
				$this->manager->closeTask($id);
			} else {
				$this->manager->closeTask($id, 'failed');
			}
		}
	}
}
