<?php
class ActionScheduler_CanceledAction extends ActionScheduler_FinishedAction {
	public function __construct( $hook, array $args = array(), ActionScheduler_Schedule $schedule = null, $group = '' ) {
		parent::__construct( $hook, $args, $schedule, $group );
		$this->set_schedule( new ActionScheduler_NullSchedule() );
	}
}
