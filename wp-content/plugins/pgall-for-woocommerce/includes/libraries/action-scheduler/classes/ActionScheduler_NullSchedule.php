<?php
class ActionScheduler_NullSchedule implements ActionScheduler_Schedule {

	public function next( DateTime $after = NULL ) {
		return NULL;
	}
	public function is_recurring() {
		return false;
	}
}
 