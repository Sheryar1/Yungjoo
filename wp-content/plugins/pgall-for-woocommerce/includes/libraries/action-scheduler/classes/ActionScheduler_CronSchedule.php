<?php
class ActionScheduler_CronSchedule implements ActionScheduler_Schedule {
	private $start = NULL;
	private $start_timestamp = 0;
	private $cron = NULL;

	public function __construct( DateTime $start, CronExpression $cron ) {
		$this->start = $start;
		$this->cron = $cron;
	}
	public function next( DateTime $after = NULL ) {
		$after = empty($after) ? clone $this->start : clone $after;
		return $this->cron->getNextRunDate($after, 0, false);
	}
	public function is_recurring() {
		return true;
	}
	public function __sleep() {
		$this->start_timestamp = $this->start->getTimestamp();
		return array(
			'start_timestamp',
			'cron'
		);
	}

	public function __wakeup() {
		$this->start = as_get_datetime_object($this->start_timestamp);
	}
}

