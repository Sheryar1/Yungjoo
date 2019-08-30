<?php
class ActionScheduler_WPCLI_QueueRunner extends ActionScheduler_Abstract_QueueRunner {
	protected $actions;
	protected $claim;
	protected $progress_bar;
	public function __construct( ActionScheduler_Store $store = null, ActionScheduler_FatalErrorMonitor $monitor = null, ActionScheduler_QueueCleaner $cleaner = null ) {
		if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			throw new Exception( __( 'The ' . __CLASS__ . ' class can only be run within WP CLI.', 'action-scheduler' ) );
		}

		parent::__construct( $store, $monitor, $cleaner );
	}
	public function setup( $batch_size, $hooks = array(), $group = '', $force = false ) {
		$this->run_cleanup();
		$this->add_hooks();

		// Check to make sure there aren't too many concurrent processes running.
		$claim_count = $this->store->get_claim_count();
		$too_many    = $claim_count >= $this->get_allowed_concurrent_batches();
		if ( $too_many ) {
			if ( $force ) {
				WP_CLI::warning( __( 'There are too many concurrent batches, but the run is forced to continue.', 'action-scheduler' ) );
			} else {
				WP_CLI::error( __( 'There are too many concurrent batches.', 'action-scheduler' ) );
			}
		}

		// Stake a claim and store it.
		$this->claim = $this->store->stake_claim( $batch_size, null, $hooks, $group );
		$this->monitor->attach( $this->claim );
		$this->actions = $this->claim->get_actions();

		return count( $this->actions );
	}
	protected function add_hooks() {
		add_action( 'action_scheduler_before_execute', array( $this, 'before_execute' ) );
		add_action( 'action_scheduler_after_execute', array( $this, 'after_execute' ) );
		add_action( 'action_scheduler_failed_execution', array( $this, 'action_failed' ), 10, 2 );
	}
	protected function setup_progress_bar() {
		$count              = count( $this->actions );
		$this->progress_bar = \WP_CLI\Utils\make_progress_bar(
			sprintf( _n( 'Running %d action', 'Running %d actions', $count, 'action-scheduler' ), number_format_i18n( $count ) ),
			$count
		);
	}
	public function run() {
		do_action( 'action_scheduler_before_process_queue' );
		$this->setup_progress_bar();
		foreach ( $this->actions as $action_id ) {
			// Error if we lost the claim.
			if ( ! in_array( $action_id, $this->store->find_actions_by_claim_id( $this->claim->get_id() ) ) ) {
				WP_CLI::warning( __( 'The claim has been lost. Aborting current batch.', 'action-scheduler' ) );
				break;
			}

			$this->process_action( $action_id );
			$this->progress_bar->tick();

			// Free up memory after every 50 items
			if ( 0 === $this->progress_bar->current() % 50 ) {
				$this->stop_the_insanity();
			}
		}

		$completed = $this->progress_bar->current();
		$this->progress_bar->finish();
		$this->store->release_claim( $this->claim );
		do_action( 'action_scheduler_after_process_queue' );

		return $completed;
	}
	public function before_execute( $action_id ) {
		WP_CLI::log( sprintf( __( 'Started processing action %s', 'action-scheduler' ), $action_id ) );
	}
	public function after_execute( $action_id ) {
		WP_CLI::log( sprintf( __( 'Completed processing action %s', 'action-scheduler' ), $action_id ) );
	}
	public function action_failed( $action_id, $exception ) {
		WP_CLI::error(
			sprintf( __( 'Error processing action %1$s: %2$s', 'action-scheduler' ), $action_id, $exception->getMessage() ),
			false
		);
	}
	protected function stop_the_insanity( $sleep_time = 0 ) {
		if ( 0 < $sleep_time ) {
			WP_CLI::warning( sprintf( 'Stopped the insanity for %d %s', $sleep_time, _n( 'second', 'seconds', $sleep_time ) ) );
			sleep( $sleep_time );
		}

		WP_CLI::warning( __( 'Attempting to reduce used memory...', 'action-scheduler' ) );
		global $wpdb, $wp_object_cache;

		$wpdb->queries = array();

		if ( ! is_object( $wp_object_cache ) ) {
			return;
		}

		$wp_object_cache->group_ops      = array();
		$wp_object_cache->stats          = array();
		$wp_object_cache->memcache_debug = array();
		$wp_object_cache->cache          = array();

		if ( is_callable( array( $wp_object_cache, '__remoteset' ) ) ) {
			call_user_func( array( $wp_object_cache, '__remoteset' ) ); // important
		}
	}
}
