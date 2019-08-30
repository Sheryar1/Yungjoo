<?php
class ActionScheduler_wpPostStore extends ActionScheduler_Store {
	const POST_TYPE = 'scheduled-action';
	const GROUP_TAXONOMY = 'action-group';
	const SCHEDULE_META_KEY = '_action_manager_schedule';
	protected $local_timezone = NULL;
	private static $max_index_length = 191;

	public function save_action( ActionScheduler_Action $action, DateTime $scheduled_date = NULL ){
		try {
			$this->validate_action( $action );
			$post_array = $this->create_post_array( $action, $scheduled_date );
			$post_id = $this->save_post_array( $post_array );
			$this->save_post_schedule( $post_id, $action->get_schedule() );
			$this->save_action_group( $post_id, $action->get_group() );
			do_action( 'action_scheduler_stored_action', $post_id );
			return $post_id;
		} catch ( Exception $e ) {
			throw new RuntimeException( sprintf( __('Error saving action: %s', 'action-scheduler'), $e->getMessage() ), 0 );
		}
	}

	protected function create_post_array( ActionScheduler_Action $action, DateTime $scheduled_date = NULL ) {
		$post = array(
			'post_type' => self::POST_TYPE,
			'post_title' => $action->get_hook(),
			'post_content' => wp_json_encode($action->get_args()),
			'post_status' => ( $action->is_finished() ? 'publish' : 'pending' ),
			'post_date_gmt' => $this->get_scheduled_date_string( $action, $scheduled_date ),
			'post_date'     => $this->get_scheduled_date_string_local( $action, $scheduled_date ),
		);
		return $post;
	}

	protected function save_post_array( $post_array ) {
		add_filter( 'wp_insert_post_data', array( $this, 'filter_insert_post_data' ), 10, 1 );
		$post_id = wp_insert_post($post_array);
		remove_filter( 'wp_insert_post_data', array( $this, 'filter_insert_post_data' ), 10 );

		if ( is_wp_error($post_id) || empty($post_id) ) {
			throw new RuntimeException(__('Unable to save action.', 'action-scheduler'));
		}
		return $post_id;
	}

	public function filter_insert_post_data( $postdata ) {
		if ( $postdata['post_type'] == self::POST_TYPE ) {
			$postdata['post_author'] = 0;
			if ( $postdata['post_status'] == 'future' ) {
				$postdata['post_status'] = 'publish';
			}
		}
		return $postdata;
	}

	protected function save_post_schedule( $post_id, $schedule ) {
		update_post_meta( $post_id, self::SCHEDULE_META_KEY, $schedule );
	}

	protected function save_action_group( $post_id, $group ) {
		if ( empty($group) ) {
			wp_set_object_terms( $post_id, array(), self::GROUP_TAXONOMY, FALSE );
		} else {
			wp_set_object_terms( $post_id, array($group), self::GROUP_TAXONOMY, FALSE );
		}
	}

	public function fetch_action( $action_id ) {
		$post = $this->get_post( $action_id );
		if ( empty($post) || $post->post_type != self::POST_TYPE ) {
			return $this->get_null_action();
		}
		return $this->make_action_from_post($post);
	}

	protected function get_post( $action_id ) {
		if ( empty($action_id) ) {
			return NULL;
		}
		return get_post($action_id);
	}

	protected function get_null_action() {
		return new ActionScheduler_NullAction();
	}

	protected function make_action_from_post( $post ) {
		$hook = $post->post_title;
		$args = json_decode( $post->post_content, true );

		// Handle args that do not decode properly.
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $args ) ) {
			throw ActionScheduler_InvalidActionException::from_decoding_args( $post->ID );
		}

		$schedule = get_post_meta( $post->ID, self::SCHEDULE_META_KEY, true );
		if ( empty($schedule) ) {
			$schedule = new ActionScheduler_NullSchedule();
		}
		$group = wp_get_object_terms( $post->ID, self::GROUP_TAXONOMY, array('fields' => 'names') );
		$group = empty( $group ) ? '' : reset($group);

		return ActionScheduler::factory()->get_stored_action( $this->get_action_status_by_post_status( $post->post_status ), $hook, $args, $schedule, $group );
	}
	protected function get_action_status_by_post_status( $post_status ) {

		switch ( $post_status ) {
			case 'publish' :
				$action_status = self::STATUS_COMPLETE;
				break;
			case 'trash' :
				$action_status = self::STATUS_CANCELED;
				break;
			default :
				if ( ! array_key_exists( $post_status, $this->get_status_labels() ) ) {
					throw new InvalidArgumentException( sprintf( 'Invalid post status: "%s". No matching action status available.', $post_status ) );
				}
				$action_status = $post_status;
				break;
		}

		return $action_status;
	}
	protected function get_post_status_by_action_status( $action_status ) {

		switch ( $action_status ) {
			case self::STATUS_COMPLETE :
				$post_status = 'publish';
				break;
			case self::STATUS_CANCELED :
				$post_status = 'trash';
				break;
			default :
				if ( ! array_key_exists( $action_status, $this->get_status_labels() ) ) {
					throw new InvalidArgumentException( sprintf( 'Invalid action status: "%s".', $action_status ) );
				}
				$post_status = $action_status;
				break;
		}

		return $post_status;
	}
	public function find_action( $hook, $params = array() ) {
		$params = wp_parse_args( $params, array(
			'args' => NULL,
			'status' => ActionScheduler_Store::STATUS_PENDING,
			'group' => '',
		));
		global $wpdb;
		$query = "SELECT p.ID FROM {$wpdb->posts} p";
		$args = array();
		if ( !empty($params['group']) ) {
			$query .= " INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID";
			$query .= " INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id=tt.term_taxonomy_id";
			$query .= " INNER JOIN {$wpdb->terms} t ON tt.term_id=t.term_id AND t.slug=%s";
			$args[] = $params['group'];
		}
		$query .= " WHERE p.post_title=%s";
		$args[] = $hook;
		$query .= " AND p.post_type=%s";
		$args[] = self::POST_TYPE;
		if ( !is_null($params['args']) ) {
			$query .= " AND p.post_content=%s";
			$args[] = wp_json_encode($params['args']);
		}

		if ( ! empty( $params['status'] ) ) {
			$query .= " AND p.post_status=%s";
			$args[] = $this->get_post_status_by_action_status( $params['status'] );
		}

		switch ( $params['status'] ) {
			case self::STATUS_COMPLETE:
			case self::STATUS_RUNNING:
			case self::STATUS_FAILED:
				$order = 'DESC'; // Find the most recent action that matches
				break;
			case self::STATUS_PENDING:
			default:
				$order = 'ASC'; // Find the next action that matches
				break;
		}
		$query .= " ORDER BY post_date_gmt $order LIMIT 1";

		$query = $wpdb->prepare( $query, $args );

		$id = $wpdb->get_var($query);
		return $id;
	}
	protected function get_query_actions_sql( array $query, $select_or_count = 'select' ) {

		if ( ! in_array( $select_or_count, array( 'select', 'count' ) ) ) {
			throw new InvalidArgumentException(__('Invalid schedule. Cannot save action.', 'action-scheduler'));
		}

		$query = wp_parse_args( $query, array(
			'hook' => '',
			'args' => NULL,
			'date' => NULL,
			'date_compare' => '<=',
			'modified' => NULL,
			'modified_compare' => '<=',
			'group' => '',
			'status' => '',
			'claimed' => NULL,
			'per_page' => 5,
			'offset' => 0,
			'orderby' => 'date',
			'order' => 'ASC',
			'search' => '',
		) );
		global $wpdb;
		$sql  = ( 'count' === $select_or_count ) ? 'SELECT count(p.ID)' : 'SELECT p.ID ';
		$sql .= "FROM {$wpdb->posts} p";
		$sql_params = array();
		if ( ! empty( $query['group'] ) || 'group' === $query['orderby'] ) {
			$sql .= " INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID";
			$sql .= " INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id=tt.term_taxonomy_id";
			$sql .= " INNER JOIN {$wpdb->terms} t ON tt.term_id=t.term_id";

			if ( ! empty( $query['group'] ) ) {
				$sql .= " AND t.slug=%s";
				$sql_params[] = $query['group'];
			}
		}
		$sql .= " WHERE post_type=%s";
		$sql_params[] = self::POST_TYPE;
		if ( $query['hook'] ) {
			$sql .= " AND p.post_title=%s";
			$sql_params[] = $query['hook'];
		}
		if ( !is_null($query['args']) ) {
			$sql .= " AND p.post_content=%s";
			$sql_params[] = wp_json_encode($query['args']);
		}

		if ( ! empty( $query['status'] ) ) {
			$sql .= " AND p.post_status=%s";
			$sql_params[] = $this->get_post_status_by_action_status( $query['status'] );
		}

		if ( $query['date'] instanceof DateTime ) {
			$date = clone $query['date'];
			$date->setTimezone( new DateTimeZone('UTC') );
			$date_string = $date->format('Y-m-d H:i:s');
			$comparator = $this->validate_sql_comparator($query['date_compare']);
			$sql .= " AND p.post_date_gmt $comparator %s";
			$sql_params[] = $date_string;
		}

		if ( $query['modified'] instanceof DateTime ) {
			$modified = clone $query['modified'];
			$modified->setTimezone( new DateTimeZone('UTC') );
			$date_string = $modified->format('Y-m-d H:i:s');
			$comparator = $this->validate_sql_comparator($query['modified_compare']);
			$sql .= " AND p.post_modified_gmt $comparator %s";
			$sql_params[] = $date_string;
		}

		if ( $query['claimed'] === TRUE ) {
			$sql .= " AND p.post_password != ''";
		} elseif ( $query['claimed'] === FALSE ) {
			$sql .= " AND p.post_password = ''";
		} elseif ( !is_null($query['claimed']) ) {
			$sql .= " AND p.post_password = %s";
			$sql_params[] = $query['claimed'];
		}

		if ( ! empty( $query['search'] ) ) {
			$sql .= " AND (p.post_title LIKE %s OR p.post_content LIKE %s OR p.post_password LIKE %s)";
			for( $i = 0; $i < 3; $i++ ) {
				$sql_params[] = sprintf( '%%%s%%', $query['search'] );
			}
		}

		if ( 'select' === $select_or_count ) {
			switch ( $query['orderby'] ) {
				case 'hook':
					$orderby = 'p.post_title';
					break;
				case 'group':
					$orderby = 't.name';
					break;
				case 'status':
					$orderby = 'p.post_status';
					break;
				case 'modified':
					$orderby = 'p.post_modified';
					break;
				case 'claim_id':
					$orderby = 'p.post_password';
					break;
				case 'schedule':
				case 'date':
				default:
					$orderby = 'p.post_date_gmt';
					break;
			}
			if ( 'ASC' === strtoupper( $query['order'] ) ) {
				$order = 'ASC';
			} else {
				$order = 'DESC';
			}
			$sql .= " ORDER BY $orderby $order";
			if ( $query['per_page'] > 0 ) {
				$sql .= " LIMIT %d, %d";
				$sql_params[] = $query['offset'];
				$sql_params[] = $query['per_page'];
			}
		}

		return $wpdb->prepare( $sql, $sql_params );
	}
	public function query_actions( $query = array(), $query_type = 'select' ) {
		global $wpdb;

		$sql = $this->get_query_actions_sql( $query, $query_type );

		return ( 'count' === $query_type ) ? $wpdb->get_var( $sql ) : $wpdb->get_col( $sql );
	}
	public function action_counts() {

		$action_counts_by_status = array();
		$action_stati_and_labels = $this->get_status_labels();
		$posts_count_by_status   = (array) wp_count_posts( self::POST_TYPE, 'readable' );

		foreach ( $posts_count_by_status as $post_status_name => $count ) {

			try {
				$action_status_name = $this->get_action_status_by_post_status( $post_status_name );
			} catch ( Exception $e ) {
				// Ignore any post statuses that aren't for actions
				continue;
			}
			if ( array_key_exists( $action_status_name, $action_stati_and_labels ) ) {
				$action_counts_by_status[ $action_status_name ] = $count;
			}
		}

		return $action_counts_by_status;
	}
	public function cancel_action( $action_id ) {
		$post = get_post($action_id);
		if ( empty($post) || ($post->post_type != self::POST_TYPE) ) {
			throw new InvalidArgumentException(sprintf(__('Unidentified action %s', 'action-scheduler'), $action_id));
		}
		do_action( 'action_scheduler_canceled_action', $action_id );
		wp_trash_post($action_id);
	}

	public function delete_action( $action_id ) {
		$post = get_post($action_id);
		if ( empty($post) || ($post->post_type != self::POST_TYPE) ) {
			throw new InvalidArgumentException(sprintf(__('Unidentified action %s', 'action-scheduler'), $action_id));
		}
		do_action( 'action_scheduler_deleted_action', $action_id );
		wp_delete_post($action_id, TRUE);
	}
	public function get_date( $action_id ) {
		$next = $this->get_date_gmt( $action_id );
		return ActionScheduler_TimezoneHelper::set_local_timezone( $next );
	}
	public function get_date_gmt( $action_id ) {
		$post = get_post($action_id);
		if ( empty($post) || ($post->post_type != self::POST_TYPE) ) {
			throw new InvalidArgumentException(sprintf(__('Unidentified action %s', 'action-scheduler'), $action_id));
		}
		if ( $post->post_status == 'publish' ) {
			return as_get_datetime_object($post->post_modified_gmt);
		} else {
			return as_get_datetime_object($post->post_date_gmt);
		}
	}
	public function stake_claim( $max_actions = 10, DateTime $before_date = null, $hooks = array(), $group = '' ) {
		$claim_id = $this->generate_claim_id();
		$this->claim_actions( $claim_id, $max_actions, $before_date, $hooks, $group );
		$action_ids = $this->find_actions_by_claim_id( $claim_id );

		return new ActionScheduler_ActionClaim( $claim_id, $action_ids );
	}
	public function get_claim_count(){
		global $wpdb;

		$sql = "SELECT COUNT(DISTINCT post_password) FROM {$wpdb->posts} WHERE post_password != '' AND post_type = %s AND post_status IN ('in-progress','pending')";
		$sql = $wpdb->prepare( $sql, array( self::POST_TYPE ) );

		return $wpdb->get_var( $sql );
	}

	protected function generate_claim_id() {
		$claim_id = md5(microtime(true) . rand(0,1000));
		return substr($claim_id, 0, 20); // to fit in db field with 20 char limit
	}
	protected function claim_actions( $claim_id, $limit, DateTime $before_date = null, $hooks = array(), $group = '' ) {
		// Set up initial variables.
		$date      = null === $before_date ? as_get_datetime_object() : clone $before_date;
		$limit_ids = ! empty( $group );
		$ids       = $limit_ids ? $this->get_actions_by_group( $group, $limit, $date ) : array();

		// If limiting by IDs and no posts found, then return early since we have nothing to update.
		if ( $limit_ids && 0 === count( $ids ) ) {
			return 0;
		}
		global $wpdb;
		$update = "UPDATE {$wpdb->posts} SET post_password = %s, post_modified_gmt = %s, post_modified = %s";
		$params = array(
			$claim_id,
			current_time( 'mysql', true ),
			current_time( 'mysql' ),
		);

		// Build initial WHERE clause.
		$where    = "WHERE post_type = %s AND post_status = %s AND post_password = ''";
		$params[] = self::POST_TYPE;
		$params[] = ActionScheduler_Store::STATUS_PENDING;

		if ( ! empty( $hooks ) ) {
			$placeholders = array_fill( 0, count( $hooks ), '%s' );
			$where       .= ' AND post_title IN (' . join( ', ', $placeholders ) . ')';
			$params       = array_merge( $params, array_values( $hooks ) );
		}
		if ( $limit_ids ) {
			$where .= ' AND ID IN (' . join( ',', $ids ) . ')';
		} else {
			$where .= ' AND post_date_gmt <= %s';
			$params[] = $date->format( 'Y-m-d H:i:s' );
		}

		// Add the ORDER BY clause and,ms limit.
		$order    = 'ORDER BY menu_order ASC, post_date_gmt ASC, ID ASC LIMIT %d';
		$params[] = $limit;

		// Run the query and gather results.
		$rows_affected = $wpdb->query( $wpdb->prepare( "{$update} {$where} {$order}", $params ) );
		if ( $rows_affected === false ) {
			throw new RuntimeException( __( 'Unable to claim actions. Database error.', 'action-scheduler' ) );
		}

		return (int) $rows_affected;
	}
	protected function get_actions_by_group( $group, $limit, DateTime $date ) {
		// Ensure the group exists before continuing.
		if ( ! term_exists( $group, self::GROUP_TAXONOMY )) {
			throw new InvalidArgumentException( sprintf( __( 'The group "%s" does not exist.', 'action-scheduler' ), $group ) );
		}

		// Set up a query for post IDs to use later.
		$query      = new WP_Query();
		$query_args = array(
			'fields'           => 'ids',
			'post_type'        => self::POST_TYPE,
			'post_status'      => ActionScheduler_Store::STATUS_PENDING,
			'has_password'     => false,
			'posts_per_page'   => $limit * 3,
			'suppress_filters' => true,
			'no_found_rows'    => true,
			'orderby'          => array(
				'menu_order' => 'ASC',
				'date'       => 'ASC',
				'ID'         => 'ASC',
			),
			'date_query'       => array(
				'column' => 'post_date',
				array(
					'compare' => '<=',
					'year'    => $date->format( 'Y' ),
					'month'   => $date->format( 'n' ),
					'day'     => $date->format( 'j' ),
					'hour'    => $date->format( 'G' ),
					'minute'  => $date->format( 'i' ),
					'second'  => $date->format( 's' ),
				),
			),
			'tax_query' => array(
				array(
					'taxonomy'         => self::GROUP_TAXONOMY,
					'field'            => 'slug',
					'terms'            => $group,
					'include_children' => false,
				),
			),
		);

		return $query->query( $query_args );
	}
	public function find_actions_by_claim_id( $claim_id ) {
		global $wpdb;
		$sql = "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_password = %s";
		$sql = $wpdb->prepare( $sql, array( self::POST_TYPE, $claim_id ) );
		$action_ids = $wpdb->get_col( $sql );
		return $action_ids;
	}

	public function release_claim( ActionScheduler_ActionClaim $claim ) {
		$action_ids = $this->find_actions_by_claim_id( $claim->get_id() );
		if ( empty($action_ids) ) {
			return; // nothing to do
		}
		$action_id_string = implode(',', array_map('intval', $action_ids));
		global $wpdb;
		$sql = "UPDATE {$wpdb->posts} SET post_password = '' WHERE ID IN ($action_id_string) AND post_password = %s";
		$sql = $wpdb->prepare( $sql, array( $claim->get_id() ) );
		$result = $wpdb->query($sql);
		if ( $result === false ) {
			throw new RuntimeException( sprintf( __('Unable to unlock claim %s. Database error.', 'action-scheduler'), $claim->get_id() ) );
		}
	}
	public function unclaim_action( $action_id ) {
		global $wpdb;
		$sql = "UPDATE {$wpdb->posts} SET post_password = '' WHERE ID = %d AND post_type = %s";
		$sql = $wpdb->prepare( $sql, $action_id, self::POST_TYPE );
		$result = $wpdb->query($sql);
		if ( $result === false ) {
			throw new RuntimeException( sprintf( __('Unable to unlock claim on action %s. Database error.', 'action-scheduler'), $action_id ) );
		}
	}

	public function mark_failure( $action_id ) {
		global $wpdb;
		$sql = "UPDATE {$wpdb->posts} SET post_status = %s WHERE ID = %d AND post_type = %s";
		$sql = $wpdb->prepare( $sql, self::STATUS_FAILED, $action_id, self::POST_TYPE );
		$result = $wpdb->query($sql);
		if ( $result === false ) {
			throw new RuntimeException( sprintf( __('Unable to mark failure on action %s. Database error.', 'action-scheduler'), $action_id ) );
		}
	}
	public function get_claim_id( $action_id ) {
		return $this->get_post_column( $action_id, 'post_password' );
	}
	public function get_status( $action_id ) {
		$status = $this->get_post_column( $action_id, 'post_status' );

		if ( $status === null ) {
			throw new InvalidArgumentException( __( 'Invalid action ID. No status found.', 'action-scheduler' ) );
		}

		return $this->get_action_status_by_post_status( $status );
	}

	private function get_post_column( $action_id, $column_name ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT {$column_name} FROM {$wpdb->posts} WHERE ID=%d AND post_type=%s", $action_id, self::POST_TYPE ) );
	}
	public function log_execution( $action_id ) {
		global $wpdb;

		$sql = "UPDATE {$wpdb->posts} SET menu_order = menu_order+1, post_status=%s, post_modified_gmt = %s, post_modified = %s WHERE ID = %d AND post_type = %s";
		$sql = $wpdb->prepare( $sql, self::STATUS_RUNNING, current_time('mysql', true), current_time('mysql'), $action_id, self::POST_TYPE );
		$wpdb->query($sql);
	}


	public function mark_complete( $action_id ) {
		$post = get_post($action_id);
		if ( empty($post) || ($post->post_type != self::POST_TYPE) ) {
			throw new InvalidArgumentException(sprintf(__('Unidentified action %s', 'action-scheduler'), $action_id));
		}
		add_filter( 'wp_insert_post_data', array( $this, 'filter_insert_post_data' ), 10, 1 );
		$result = wp_update_post(array(
			'ID' => $action_id,
			'post_status' => 'publish',
		), TRUE);
		remove_filter( 'wp_insert_post_data', array( $this, 'filter_insert_post_data' ), 10 );
		if ( is_wp_error($result) ) {
			throw new RuntimeException($result->get_error_message());
		}
	}
	protected function validate_action( ActionScheduler_Action $action ) {
		if ( strlen( wp_json_encode( $action->get_args() ) ) > self::$max_index_length ) {
			_doing_it_wrong( 'ActionScheduler_Action::$args', sprintf( 'To ensure the action args column can be indexed, action args should not be more than %d characters when encoded as JSON. Support for strings longer than this will be removed in a future version.', self::$max_index_length ), '2.1.0' );
		}
	}
	public function init() {
		$post_type_registrar = new ActionScheduler_wpPostStore_PostTypeRegistrar();
		$post_type_registrar->register();

		$post_status_registrar = new ActionScheduler_wpPostStore_PostStatusRegistrar();
		$post_status_registrar->register();

		$taxonomy_registrar = new ActionScheduler_wpPostStore_TaxonomyRegistrar();
		$taxonomy_registrar->register();
	}
}
