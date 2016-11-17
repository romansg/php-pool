<?php

/**
 * Interface to the pool
 */
class Manager
{
	private $pdo;
	private $stmtAddTask;
	private $stmtCloseTask;

	/**
	 * Constructor.
	 * Builds a PDO object with given dsn and credentials. Prepare some frequent statements.
	 */
	public function __construct($dsn, $user = '', $password = '') {
		$this->pdo = new \PDO($dsn, $user, $password);

		$this->stmtAddTask = $this->pdo->prepare("insert into task (jobId, data) values(:jobId, :data)");
		$this->stmtCloseTask = $this->pdo->prepare("update task set status = :status where id = :id");
	}
	
	/**
	 * Adds a job to the pool
	 *
	 * @param mixed $data Anything you want to store in the pool as a job
	 * @return integer Job's id
	 */
	public function addJob($data) {
		$stmt = $this->pdo->prepare("insert into job (data) values(:data)");
		$stmt->bindValue(':data', serialize($data));
		$stmt->execute();

		return $this->pdo->lastInsertId();
	}

	/**
	 * Adds a task to the pool
	 *
	 * @param integer $jobId Job's id
	 * @param $data Anything you want to add to the pool as a job's task
	 * @return integer Task's id
	 */
	public function addTask($jobId, $data) {
		$this->stmtAddTask->bindValue(':jobId', $jobId, \PDO::PARAM_INT);
		$this->stmtAddTask->bindValue(':data', serialize($data));
		$this->stmtAddTask->execute();

		return $this->pdo->lastInsertId();
	}

	/**
	 * Retrieves a job from the pool
	 *
	 * @param integer Job's id
	 * @return mixed Job's data
	 */
	public function getJob($jobId) {
		$stmt = $this->pdo->prepare("select data from job where id = :id");
		$stmt->bindValue(':id', $jobId, \PDO::PARAM_INT);
		$stmt->execute();

		return unserialize($stmt->fetchColumn());
	}
	
	/**
	 * Collects pending tasks from the pool
	 *
	 * This method collects pending tasks (tasks that have not been processed) associated with a given job and reserve them
	 * so that no other process can pick them. Once a task is collected it should be dispatched and marked as done or failed
	 * @see Pool\Manager::closeTask
	 *
	 * @param integer Owner job's id
	 * @param integer $count Number of task to be retrieved (-1 means all tasks)
	 * @return array Numeric array of tasks
	 */
	public function collectTasks($jobId, $count = -1) {
		// Signature to mark tasks as reserved
		$signature = bin2hex(openssl_random_pseudo_bytes(8));

		// Before selecting, reserve $count non reserved pending tasks for this job
		$sql = "update task set signature = :signature where jobId = :jobId and status = 'pending' and signature = ''";

		if ($count != -1 ) {
			$sql .= " limit $count";
		}

		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(':signature', $signature);
		$stmt->bindValue(':jobId', $jobId, \PDO::PARAM_INT);
		$stmt->execute();

		// Now select the tasks that were reserved above
		$stmt = $this->pdo->prepare("select * from task where jobId = :jobId and signature = :signature");
		$stmt->bindValue(':jobId', $jobId, \PDO::PARAM_INT);
		$stmt->bindValue(':signature', $signature);
		$stmt->execute();

		$tasks = array();

		while ($task = $stmt->fetch(\PDO::FETCH_OBJ)) {
			$tasks[$task->id] = unserialize($task->data);
		}

		return $tasks;
	}

	/**
	 * Closes a task
	 *
	 * @param integer $taskId Task's id
	 * @param string $status Final task's status (either 'done' or 'failed')
	 * @return void
	 */
	public function closeTask($taskId, $status = 'done') {
		$this->stmtCloseTask->bindValue(':id', $taskId, \PDO::PARAM_INT);
		$this->stmtCloseTask->bindValue(':status', $status);
		$this->stmtCloseTask->execute();
	}

	/**
	 * Reports all non deleted jobs
	 */
	public function viewJobs() {
		$stmt = $this->pdo->query("select * from job where deleted = 0 order by id");

		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}
}
