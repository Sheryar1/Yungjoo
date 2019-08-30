<?php
function as_schedule_single_action( $timestamp, $hook, $args = array(), $group = '' ) {
	return ActionScheduler::factory()->single( $hook, $args, $timestamp, $group );
}
function as_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '' ) {
	return ActionScheduler::factory()->recurring( $hook, $args, $timestamp, $interval_in_seconds, $group );
}
function as_schedule_cron_action( $timestamp, $schedule, $hook, $args = array(), $group = '' ) {
	return ActionScheduler::factory()->cron( $hook, $args, $timestamp, $schedule, $group );
}
function as_unschedule_action( $hook, $args = array(), $group = '' ) {
	$params = array();
	if ( is_array($args) ) {
		$params['args'] = $args;
	}
	if ( !empty($group) ) {
		$params['group'] = $group;
	}
	$job_id = ActionScheduler::store()->find_action( $hook, $params );

	if ( ! empty( $job_id ) ) {
		ActionScheduler::store()->cancel_action( $job_id );
	}

	return $job_id;
}
function as_unschedule_all_actions( $hook, $args = array(), $group = '' ) {
	do {
		$unscheduled_action = as_unschedule_action( $hook, $args, $group );
	} while ( ! empty( $unscheduled_action ) );
}
function as_next_scheduled_action( $hook, $args = NULL, $group = '' ) {
	$params = array();
	if ( is_array($args) ) {
		$params['args'] = $args;
	}
	if ( !empty($group) ) {
		$params['group'] = $group;
	}
	$job_id = ActionScheduler::store()->find_action( $hook, $params );
	if ( empty($job_id) ) {
		return false;
	}
	$job = ActionScheduler::store()->fetch_action( $job_id );
	$next = $job->get_schedule()->next();
	if ( $next ) {
		return (int)($next->format('U'));
	}
	return false;
}
function as_get_scheduled_actions( $args = array(), $return_format = OBJECT ) {
	$store = ActionScheduler::store();
	foreach ( array('date', 'modified') as $key ) {
		if ( isset($args[$key]) ) {
			$args[$key] = as_get_datetime_object($args[$key]);
		}
	}
	$ids = $store->query_actions( $args );

	if ( $return_format == 'ids' || $return_format == 'int' ) {
		return $ids;
	}

	$actions = array();
	foreach ( $ids as $action_id ) {
		$actions[$action_id] = $store->fetch_action( $action_id );
	}

	if ( $return_format == ARRAY_A ) {
		foreach ( $actions as $action_id => $action_object ) {
			$actions[$action_id] = get_object_vars($action_object);
		}
	}

	return $actions;
}
function as_get_datetime_object( $date_string = null, $timezone = 'UTC' ) {
	if ( is_object( $date_string ) && $date_string instanceof DateTime ) {
		$date = new ActionScheduler_DateTime( $date_string->format( 'Y-m-d H:i:s' ), new DateTimeZone( $timezone ) );
	} elseif ( is_numeric( $date_string ) ) {
		$date = new ActionScheduler_DateTime( '@' . $date_string, new DateTimeZone( $timezone ) );
	} else {
		$date = new ActionScheduler_DateTime( $date_string, new DateTimeZone( $timezone ) );
	}
	return $date;
}
