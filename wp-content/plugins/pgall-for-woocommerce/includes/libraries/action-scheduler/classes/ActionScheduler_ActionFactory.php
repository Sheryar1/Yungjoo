<?php
class ActionScheduler_ActionFactory {
	public function get_stored_action( $status, $hook, array $args = array(), ActionScheduler_Schedule $schedule = null, $group = '' ) {

		switch ( $status ) {
			case ActionScheduler_Store::STATUS_PENDING :
				$action_class = 'ActionScheduler_Action';
				break;
			case ActionScheduler_Store::STATUS_CANCELED :
				$action_class = 'ActionScheduler_CanceledAction';
				break;
			default :
				$action_class = 'ActionScheduler_FinishedAction';
				break;
		}

		$action_class = apply_filters( 'action_scheduler_stored_action_class', $action_class, $status, $hook, $args, $schedule, $group );

		$action = new $action_class( $hook, $args, $schedule, $group );
		return apply_filters( 'action_scheduler_stored_action_instance', $action, $hook, $args, $schedule, $group );
	}
	public function single( $hook, $args = array(), $when = null, $group = '' ) {
		$date = as_get_datetime_object( $when );
		$schedule = new ActionScheduler_SimpleSchedule( $date );
		$action = new ActionScheduler_Action( $hook, $args, $schedule, $group );
		return $this->store( $action );
	}
	public function recurring( $hook, $args = array(), $first = null, $interval = null, $group = '' ) {
		if ( empty($interval) ) {
			return $this->single( $hook, $args, $first, $group );
		}
		$date = as_get_datetime_object( $first );
		$schedule = new ActionScheduler_IntervalSchedule( $date, $interval );
		$action = new ActionScheduler_Action( $hook, $args, $schedule, $group );
		return $this->store( $action );
	}
	public function cron( $hook, $args = array(), $first = null, $schedule = null, $group = '' ) {
		if ( empty($schedule) ) {
			return $this->single( $hook, $args, $first, $group );
		}
		$date = as_get_datetime_object( $first );
		$cron = CronExpression::factory( $schedule );
		$schedule = new ActionScheduler_CronSchedule( $date, $cron );
		$action = new ActionScheduler_Action( $hook, $args, $schedule, $group );
		return $this->store( $action );
	}
	protected function store( ActionScheduler_Action $action ) {
		$store = ActionScheduler_Store::instance();
		return $store->save_action( $action );
	}
}
