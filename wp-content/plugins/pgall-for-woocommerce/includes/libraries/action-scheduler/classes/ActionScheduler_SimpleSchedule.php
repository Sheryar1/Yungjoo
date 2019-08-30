<?php
class ActionScheduler_SimpleSchedule implements ActionScheduler_Schedule {
	private $date = NULL;
	private $timestamp = 0;
	public function __construct( DateTime $date ) {
		$this->date = clone $date;
	}
	public function next( DateTime $after = NULL ) {
		$after = empty($after) ? as_get_datetime_object('@0') : $after;
		return ( $after > $this->date ) ? NULL : clone $this->date;
	}
	public function is_recurring() {
		return false;
	}
	public function __sleep() {
		$this->timestamp = $this->date->getTimestamp();
		return array(
			'timestamp',
		);
	}

	public function __wakeup() {
		$this->date = as_get_datetime_object($this->timestamp);
	}
}
