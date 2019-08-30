<?php
abstract class ActionScheduler_Store {
	const STATUS_COMPLETE = 'complete';
	const STATUS_PENDING  = 'pending';
	const STATUS_RUNNING  = 'in-progress';
	const STATUS_FAILED   = 'failed';
	const STATUS_CANCELED = 'canceled';
	private static $store = NULL;
	abstract public function save_action( ActionScheduler_Action $action, DateTime $scheduled_date = NULL );
	abstract public function fetch_action( $action_id );
	abstract public function find_action( $hook, $params = array() );
	abstract public function query_actions( $query = array() );
	abstract public function action_counts();
	abstract public function cancel_action( $action_id );
	abstract public function delete_action( $action_id );
	abstract public function get_date( $action_id );
	abstract public function stake_claim( $max_actions = 10, DateTime $before_date = null, $hooks = array(), $group = '' );
	abstract public function get_claim_count();
	abstract public function release_claim( ActionScheduler_ActionClaim $claim );
	abstract public function unclaim_action( $action_id );
	abstract public function mark_failure( $action_id );
	abstract public function log_execution( $action_id );
	abstract public function mark_complete( $action_id );
	abstract public function get_status( $action_id );
	abstract public function get_claim_id( $action_id );
	abstract public function find_actions_by_claim_id( $claim_id );
	protected function validate_sql_comparator( $comparison_operator ) {
		if ( in_array( $comparison_operator, array('!=', '>', '>=', '<', '<=', '=') ) ) {
			return $comparison_operator;
		}
		return '=';
	}
	protected function get_scheduled_date_string( ActionScheduler_Action $action, DateTime $scheduled_date = NULL ) {
		$next = null === $scheduled_date ? $action->get_schedule()->next() : $scheduled_date;
		if ( ! $next ) {
			throw new InvalidArgumentException( __( 'Invalid schedule. Cannot save action.', 'action-scheduler' ) );
		}
		$next->setTimezone( new DateTimeZone( 'UTC' ) );

		return $next->format( 'Y-m-d H:i:s' );
	}
	protected function get_scheduled_date_string_local( ActionScheduler_Action $action, DateTime $scheduled_date = NULL ) {
		$next = null === $scheduled_date ? $action->get_schedule()->next() : $scheduled_date;
		if ( ! $next ) {
			throw new InvalidArgumentException( __( 'Invalid schedule. Cannot save action.', 'action-scheduler' ) );
		}

		ActionScheduler_TimezoneHelper::set_local_timezone( $next );
		return $next->format( 'Y-m-d H:i:s' );
	}
	public function get_status_labels() {
		return array(
			self::STATUS_COMPLETE => __( 'Complete', 'action-scheduler' ),
			self::STATUS_PENDING  => __( 'Pending', 'action-scheduler' ),
			self::STATUS_RUNNING  => __( 'In-progress', 'action-scheduler' ),
			self::STATUS_FAILED   => __( 'Failed', 'action-scheduler' ),
			self::STATUS_CANCELED => __( 'Canceled', 'action-scheduler' ),
		);
	}

	public function init() {}
	public static function instance() {
		if ( empty(self::$store) ) {
			$class = apply_filters('action_scheduler_store_class', 'ActionScheduler_wpPostStore');
			self::$store = new $class();
		}
		return self::$store;
	}
	protected function get_local_timezone() {
		_deprecated_function( __FUNCTION__, '2.1.0', 'ActionScheduler_TimezoneHelper::set_local_timezone()' );
		return ActionScheduler_TimezoneHelper::get_local_timezone();
	}
}
