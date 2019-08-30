<?php
abstract class ActionScheduler_Abstract_QueueRunner {
	protected $cleaner;
	protected $monitor;
	protected $store;
	private $created_time;
	public function __construct( ActionScheduler_Store $store = null, ActionScheduler_FatalErrorMonitor $monitor = null, ActionScheduler_QueueCleaner $cleaner = null ) {

		$this->created_time = microtime( true );

		$this->store   = $store ? $store : ActionScheduler_Store::instance();
		$this->monitor = $monitor ? $monitor : new ActionScheduler_FatalErrorMonitor( $this->store );
		$this->cleaner = $cleaner ? $cleaner : new ActionScheduler_QueueCleaner( $this->store );
	}
	public function process_action( $action_id ) {
		try {
			do_action( 'action_scheduler_before_execute', $action_id );

			if ( ActionScheduler_Store::STATUS_PENDING !== $this->store->get_status( $action_id ) ) {
				do_action( 'action_scheduler_execution_ignored', $action_id );
				return;
			}

			$action = $this->store->fetch_action( $action_id );
			$this->store->log_execution( $action_id );
			$action->execute();
			do_action( 'action_scheduler_after_execute', $action_id );
			$this->store->mark_complete( $action_id );
		} catch ( Exception $e ) {
			$this->store->mark_failure( $action_id );
			do_action( 'action_scheduler_failed_execution', $action_id, $e );
		}
		$this->schedule_next_instance( $action );
	}
	protected function schedule_next_instance( ActionScheduler_Action $action ) {
		$schedule = $action->get_schedule();
		$next     = $schedule->next( as_get_datetime_object() );

		if ( ! is_null( $next ) && $schedule->is_recurring() ) {
			$this->store->save_action( $action, $next );
		}
	}
	protected function run_cleanup() {
		$this->cleaner->clean();
	}
	public function get_allowed_concurrent_batches() {
		return apply_filters( 'action_scheduler_queue_runner_concurrent_batches', 5 );
	}
	protected function get_maximum_execution_time() {

		// There are known hosts with a strict 60 second execution time.
		if ( defined( 'WPENGINE_ACCOUNT' ) || defined( 'PANTHEON_ENVIRONMENT' ) ) {
			$maximum_execution_time = 60;
		} elseif ( false !== strpos( getenv( 'HOSTNAME' ), '.siteground.' ) ) {
			$maximum_execution_time = 120;
		} else {
			$maximum_execution_time = ini_get( 'max_execution_time' );
		}

		return absint( apply_filters( 'action_scheduler_maximum_execution_time', $maximum_execution_time ) );
	}
	protected function get_execution_time() {
		$execution_time = microtime( true ) - $this->created_time;

		// Get the CPU time if the hosting environment uses it rather than wall-clock time to calculate a process's execution time.
		if ( function_exists( 'getrusage' ) && apply_filters( 'action_scheduler_use_cpu_execution_time', defined( 'PANTHEON_ENVIRONMENT' ) ) ) {
			$resource_usages = getrusage();

			if ( isset( $resource_usages['ru_stime.tv_usec'], $resource_usages['ru_stime.tv_usec'] ) ) {
				$execution_time = $resource_usages['ru_stime.tv_sec'] + ( $resource_usages['ru_stime.tv_usec'] / 1000000 );
			}
		}

		return $execution_time;
	}
	protected function time_likely_to_be_exceeded( $processed_actions ) {

		$execution_time        = $this->get_execution_time();
		$max_execution_time    = $this->get_maximum_execution_time();
		$time_per_action       = $execution_time / $processed_actions;
		$estimated_time        = $execution_time + ( $time_per_action * 2 );
		$likely_to_be_exceeded = $estimated_time > $this->get_maximum_execution_time();

		return apply_filters( 'action_scheduler_maximum_execution_time_likely_to_be_exceeded', $likely_to_be_exceeded, $this, $processed_actions, $execution_time, $max_execution_time );
	}
	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			$memory_limit = '128M'; // Sensible default, and minimum required by WooCommerce
		}

		if ( ! $memory_limit || -1 === $memory_limit || '-1' === $memory_limit ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32G';
		}

		return ActionScheduler_Compatibility::convert_hr_to_bytes( $memory_limit );
	}
	protected function memory_exceeded() {

		$memory_limit    = $this->get_memory_limit() * 0.90;
		$current_memory  = memory_get_usage( true );
		$memory_exceeded = $current_memory >= $memory_limit;

		return apply_filters( 'action_scheduler_memory_exceeded', $memory_exceeded, $this );
	}
	protected function batch_limits_exceeded( $processed_actions ) {
		return $this->memory_exceeded() || $this->time_likely_to_be_exceeded( $processed_actions );
	}
	abstract public function run();
}
