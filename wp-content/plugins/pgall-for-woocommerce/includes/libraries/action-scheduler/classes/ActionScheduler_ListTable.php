<?php
class ActionScheduler_ListTable extends ActionScheduler_Abstract_ListTable {
	protected $package = 'action-scheduler';
	protected $columns = array();
	protected $row_actions = array();
	protected $store;
	protected $logger;
	protected $runner;
	protected $bulk_actions = array();
	protected static $did_notification = false;
	private static $time_periods;
	public function __construct( ActionScheduler_Store $store, ActionScheduler_Logger $logger, ActionScheduler_QueueRunner $runner ) {

		$this->store  = $store;
		$this->logger = $logger;
		$this->runner = $runner;

		$this->table_header = __( 'Scheduled Actions', 'action-scheduler' );

		$this->bulk_actions = array(
			'delete' => __( 'Delete', 'action-scheduler' ),
		);

		$this->columns = array(
			'hook'        => __( 'Hook', 'action-scheduler' ),
			'status'      => __( 'Status', 'action-scheduler' ),
			'args'        => __( 'Arguments', 'action-scheduler' ),
			'group'       => __( 'Group', 'action-scheduler' ),
			'recurrence'  => __( 'Recurrence', 'action-scheduler' ),
			'schedule'    => __( 'Scheduled Date', 'action-scheduler' ),
			'log_entries' => __( 'Log', 'action-scheduler' ),
		);

		$this->sort_by = array(
			'schedule',
			'hook',
			'group',
		);

		$this->search_by = array(
			'hook',
			'args',
			'claim_id',
		);

		$request_status = $this->get_request_status();

		if ( empty( $request_status ) ) {
			$this->sort_by[] = 'status';
		} elseif ( in_array( $request_status, array( 'in-progress', 'failed' ) ) ) {
			$this->columns  += array( 'claim_id' => __( 'Claim ID', 'action-scheduler' ) );
			$this->sort_by[] = 'claim_id';
		}

		$this->row_actions = array(
			'hook' => array(
				'run' => array(
					'name'  => __( 'Run', 'action-scheduler' ),
					'desc'  => __( 'Process the action now as if it were run as part of a queue', 'action-scheduler' ),
				),
				'cancel' => array(
					'name'  => __( 'Cancel', 'action-scheduler' ),
					'desc'  => __( 'Cancel the action now to avoid it being run in future', 'action-scheduler' ),
					'class' => 'cancel trash',
				),
			),
		);

		self::$time_periods = array(
			array(
				'seconds' => YEAR_IN_SECONDS,
				'names'   => _n_noop( '%s year', '%s years', 'action-scheduler' ),
			),
			array(
				'seconds' => MONTH_IN_SECONDS,
				'names'   => _n_noop( '%s month', '%s months', 'action-scheduler' ),
			),
			array(
				'seconds' => WEEK_IN_SECONDS,
				'names'   => _n_noop( '%s week', '%s weeks', 'action-scheduler' ),
			),
			array(
				'seconds' => DAY_IN_SECONDS,
				'names'   => _n_noop( '%s day', '%s days', 'action-scheduler' ),
			),
			array(
				'seconds' => HOUR_IN_SECONDS,
				'names'   => _n_noop( '%s hour', '%s hours', 'action-scheduler' ),
			),
			array(
				'seconds' => MINUTE_IN_SECONDS,
				'names'   => _n_noop( '%s minute', '%s minutes', 'action-scheduler' ),
			),
			array(
				'seconds' => 1,
				'names'   => _n_noop( '%s second', '%s seconds', 'action-scheduler' ),
			),
		);

		parent::__construct( array(
			'singular' => 'action-scheduler',
			'plural'   => 'action-scheduler',
			'ajax'     => false,
		) );
	}
	private static function human_interval( $interval, $periods_to_include = 2 ) {

		if ( $interval <= 0 ) {
			return __( 'Now!', 'action-scheduler' );
		}

		$output = '';

		for ( $time_period_index = 0, $periods_included = 0, $seconds_remaining = $interval; $time_period_index < count( self::$time_periods ) && $seconds_remaining > 0 && $periods_included < $periods_to_include; $time_period_index++ ) {

			$periods_in_interval = floor( $seconds_remaining / self::$time_periods[ $time_period_index ]['seconds'] );

			if ( $periods_in_interval > 0 ) {
				if ( ! empty( $output ) ) {
					$output .= ' ';
				}
				$output .= sprintf( _n( self::$time_periods[ $time_period_index ]['names'][0], self::$time_periods[ $time_period_index ]['names'][1], $periods_in_interval, 'action-scheduler' ), $periods_in_interval );
				$seconds_remaining -= $periods_in_interval * self::$time_periods[ $time_period_index ]['seconds'];
				$periods_included++;
			}
		}

		return $output;
	}
	protected function get_recurrence( $action ) {
		$recurrence = $action->get_schedule();
		if ( method_exists( $recurrence, 'interval_in_seconds' ) ) {
			return sprintf( __( 'Every %s', 'action-scheduler' ), self::human_interval( $recurrence->interval_in_seconds() ) );
		}
		return __( 'Non-repeating', 'action-scheduler' );
	}
	public function column_args( array $row ) {
		if ( empty( $row['args'] ) ) {
			return '';
		}

		$row_html = '<ul>';
		foreach ( $row['args'] as $key => $value ) {
			$row_html .= sprintf( '<li><code>%s => %s</code></li>', esc_html( var_export( $key, true ) ), esc_html( var_export( $value, true ) ) );
		}
		$row_html .= '</ul>';

		return apply_filters( 'action_scheduler_list_table_column_args', $row_html, $row );
	}
	public function column_log_entries( array $row ) {

		$log_entries_html = '<ol>';

		$timezone = new DateTimezone( 'UTC' );

		foreach ( $row['log_entries'] as $log_entry ) {
			$log_entries_html .= $this->get_log_entry_html( $log_entry, $timezone );
		}

		$log_entries_html .= '</ol>';

		return $log_entries_html;
	}
	protected function get_log_entry_html( ActionScheduler_LogEntry $log_entry, DateTimezone $timezone ) {
		$date = $log_entry->get_date();
		$date->setTimezone( $timezone );
		return sprintf( '<li><strong>%s</strong><br/>%s</li>', esc_html( $date->format( 'Y-m-d H:i:s e' ) ), esc_html( $log_entry->get_message() ) );
	}
	protected function maybe_render_actions( $row, $column_name ) {
		if ( 'pending' === strtolower( $row['status'] ) ) {
			return parent::maybe_render_actions( $row, $column_name );
		}

		return '';
	}
	public function display_admin_notices() {

		if ( $this->store->get_claim_count() >= $this->runner->get_allowed_concurrent_batches() ) {
			$this->admin_notices[] = array(
				'class'   => 'updated',
				'message' => sprintf( __( 'Maximum simultaneous batches already in progress (%s queues). No actions will be processed until the current batches are complete.', 'action-scheduler' ), $this->store->get_claim_count() ),
			);
		}

		$notification = get_transient( 'action_scheduler_admin_notice' );

		if ( is_array( $notification ) ) {
			delete_transient( 'action_scheduler_admin_notice' );

			$action = $this->store->fetch_action( $notification['action_id'] );
			$action_hook_html = '<strong><code>' . $action->get_hook() . '</code></strong>';
			if ( 1 == $notification['success'] ) {
				$class = 'updated';
				switch ( $notification['row_action_type'] ) {
					case 'run' :
						$action_message_html = sprintf( __( 'Successfully executed action: %s', 'action-scheduler' ), $action_hook_html );
						break;
					case 'cancel' :
						$action_message_html = sprintf( __( 'Successfully canceled action: %s', 'action-scheduler' ), $action_hook_html );
						break;
					default :
						$action_message_html = sprintf( __( 'Successfully processed change for action: %s', 'action-scheduler' ), $action_hook_html );
						break;
				}
			} else {
				$class = 'error';
				$action_message_html = sprintf( __( 'Could not process change for action: "%s" (ID: %d). Error: %s', 'action-scheduler' ), $action_hook_html, esc_html( $notification['action_id'] ), esc_html( $notification['error_message'] ) );
			}

			$action_message_html = apply_filters( 'action_scheduler_admin_notice_html', $action_message_html, $action, $notification );

			$this->admin_notices[] = array(
				'class'   => $class,
				'message' => $action_message_html,
			);
		}

		parent::display_admin_notices();
	}
	public function column_schedule( $row ) {
		return $this->get_schedule_display_string( $row['schedule'] );
	}
	protected function get_schedule_display_string( ActionScheduler_Schedule $schedule ) {

		$schedule_display_string = '';

		if ( ! $schedule->next() ) {
			return $schedule_display_string;
		}

		$next_timestamp = $schedule->next()->getTimestamp();

		$schedule_display_string .= $schedule->next()->format( 'Y-m-d H:i:s e' );
		$schedule_display_string .= '<br/>';

		if ( gmdate( 'U' ) > $next_timestamp ) {
			$schedule_display_string .= sprintf( __( ' (%s ago)', 'action-scheduler' ), self::human_interval( gmdate( 'U' ) - $next_timestamp ) );
		} else {
			$schedule_display_string .= sprintf( __( ' (%s)', 'action-scheduler' ), self::human_interval( $next_timestamp - gmdate( 'U' ) ) );
		}

		return $schedule_display_string;
	}
	protected function bulk_delete( array $ids, $ids_sql ) {
		foreach ( $ids as $id ) {
			$this->store->delete_action( $id );
		}
	}
	protected function row_action_cancel( $action_id ) {
		$this->process_row_action( $action_id, 'cancel' );
	}
	protected function row_action_run( $action_id ) {
		$this->process_row_action( $action_id, 'run' );
	}
	protected function process_row_action( $action_id, $row_action_type ) {
		try {
			switch ( $row_action_type ) {
				case 'run' :
					$this->runner->process_action( $action_id );
					break;
				case 'cancel' :
					$this->store->cancel_action( $action_id );
					break;
			}
			$success = 1;
			$error_message = '';
		} catch ( Exception $e ) {
			$success = 0;
			$error_message = $e->getMessage();
		}

		set_transient( 'action_scheduler_admin_notice', compact( 'action_id', 'success', 'error_message', 'row_action_type' ), 30 );
	}
	public function prepare_items() {
		$this->process_bulk_action();

		$this->process_row_actions();

		if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
			// _wp_http_referer is used only on bulk actions, we remove it to keep the $_GET shorter
			wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
			exit;
		}

		$this->prepare_column_headers();

		$per_page = $this->get_items_per_page( $this->package . '_items_per_page', $this->items_per_page );
		$query = array(
			'per_page' => $per_page,
			'offset'   => $this->get_items_offset(),
			'status'   => $this->get_request_status(),
			'orderby'  => $this->get_request_orderby(),
			'order'    => $this->get_request_order(),
			'search'   => $this->get_request_search_query(),
		);

		$this->items = array();

		$total_items = $this->store->query_actions( $query, 'count' );

		$status_labels = $this->store->get_status_labels();

		foreach ( $this->store->query_actions( $query ) as $action_id ) {
			try {
				$action = $this->store->fetch_action( $action_id );
			} catch ( Exception $e ) {
				continue;
			}
			$this->items[ $action_id ] = array(
				'ID'          => $action_id,
				'hook'        => $action->get_hook(),
				'status'      => $status_labels[ $this->store->get_status( $action_id ) ],
				'args'        => $action->get_args(),
				'group'       => $action->get_group(),
				'log_entries' => $this->logger->get_logs( $action_id ),
				'claim_id'    => $this->store->get_claim_id( $action_id ),
				'recurrence'  => $this->get_recurrence( $action ),
				'schedule'    => $action->get_schedule(),
			);
		}

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}
	protected function display_filter_by_status() {
		$this->status_counts = $this->store->action_counts();
		parent::display_filter_by_status();
	}
	protected function get_search_box_button_text() {
		return __( 'Search hook, args and claim ID', 'action-scheduler' );
	}
}
