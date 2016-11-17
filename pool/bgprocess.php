<?php
namespace Pool;

/**
 * Implements a process running in the background (only unix I am afraid)
 */
class BgProcess
{
	const FMT_COMMAND = "%s %s > %s & echo $!";

	/**
	 * Constructor.
	 *
	 * @param string $command Command to be executed
	 * @param string $output Optional output file
	 */
	public function __construct($command, $output = '/dev/null') {
		$this->command = $command;
		$this->output = $output;
	}

	/**
	 * Executes the process in the background
	 *
	 * @param string $arguments,... Arguments to be passed to the process (as a space separated list)
	 * @return integer Process' id (pid)
	 */
	public function execute() {
		$params = implode(' ', func_get_args());
		return shell_exec(sprintf(self::FMT_COMMAND, $this->command, $params, $this->output));
	}
}
