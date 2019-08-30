<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
abstract class ActionScheduler_Abstract_ListTable extends WP_List_Table {
	protected $table_name;
	protected $package;
	protected $items_per_page = 10;
	protected $search_by = array();
	protected $columns = array();
	protected $row_actions = array();
	protected $ID = 'ID';
	protected $sort_by = array();

	protected $filter_by = array();
	protected $status_counts = array();
	protected $admin_notices = array();
	protected $table_header;
	protected $bulk_actions = array();
	protected function translate( $text, $context = '' ) {
		return _x( $text, $context, $this->package );
	}
	protected function get_bulk_actions() {
		$actions = array();

		foreach ( $this->bulk_actions as $action => $label ) {
			if ( ! is_callable( array( $this, 'bulk_' . $action ) ) ) {
				throw new RuntimeException( "The bulk action $action does not have a callback method" );
			}

			$actions[ $action ] = $this->translate( $label );
		}

		return $actions;
	}
	protected function process_bulk_action() {
		global $wpdb;
		// Detect when a bulk action is being triggered.
		$action = $this->current_action();

		if ( ! $action ) {
			return;
		}

		check_admin_referer( 'bulk-' . $this->_args['plural'] );

		$method   = 'bulk_' . $action;
		if ( array_key_exists( $action, $this->bulk_actions ) && is_callable( array( $this, $method ) ) && ! empty( $_GET['ID'] ) && is_array( $_GET['ID'] ) ) {
			$ids_sql = '(' . implode( ',', array_fill( 0, count( $_GET['ID'] ), '%s' ) ) . ')';
			$this->$method( $_GET['ID'], $wpdb->prepare( $ids_sql, $_GET['ID'] ) );
		}

		wp_redirect( remove_query_arg(
			array( '_wp_http_referer', '_wpnonce', 'ID', 'action', 'action2' ),
			wp_unslash( $_SERVER['REQUEST_URI'] )
		) );
		exit;
	}
	protected function bulk_delete( array $ids, $ids_sql ) {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$this->table_name} WHERE {$this->ID} IN $ids_sql" );
	}
	protected function prepare_column_headers() {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}
	public function get_sortable_columns() {
		$sort_by = array();
		foreach ( $this->sort_by as $column ) {
			$sort_by[ $column ] = array( $column, true );
		}
		return $sort_by;
	}
	public function get_columns() {
		$columns = array_merge(
			array( 'cb' => '<input type="checkbox" />' ),
			array_map( array( $this, 'translate' ), $this->columns )
		);

		return $columns;
	}
	protected function get_items_query_limit() {
		global $wpdb;

		$per_page = $this->get_items_per_page( $this->package . '_items_per_page', $this->items_per_page );
		return $wpdb->prepare( 'LIMIT %d', $per_page );
	}
	protected function get_items_offset() {
		$per_page = $this->get_items_per_page( $this->package . '_items_per_page', $this->items_per_page );
		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		return $offset;
	}
	protected function get_items_query_offset() {
		global $wpdb;

		return $wpdb->prepare( 'OFFSET %d', $this->get_items_offset() );
	}
	protected function get_items_query_order() {
		if ( empty( $this->sort_by ) ) {
			return '';
		}

		$orderby = esc_sql( $this->get_request_orderby() );
		$order   = esc_sql( $this->get_request_order() );

		return "ORDER BY {$orderby} {$order}";
	}
	protected function get_request_orderby() {

		$valid_sortable_columns = array_values( $this->sort_by );

		if ( ! empty( $_GET['orderby'] ) && in_array( $_GET['orderby'], $valid_sortable_columns ) ) {
			$orderby = sanitize_text_field( $_GET['orderby'] );
		} else {
			$orderby = $valid_sortable_columns[0];
		}

		return $orderby;
	}
	protected function get_request_order() {

		if ( ! empty( $_GET['order'] ) && 'desc' === strtolower( $_GET['order'] ) ) {
			$order = 'DESC';
		} else {
			$order = 'ASC';
		}

		return $order;
	}
	protected function get_request_status() {
		$status = ( ! empty( $_GET['status'] ) ) ? $_GET['status'] : '';
		return $status;
	}
	protected function get_request_search_query() {
		$search_query = ( ! empty( $_GET['s'] ) ) ? $_GET['s'] : '';
		return $search_query;
	}
	protected function get_table_columns() {
		$columns = array_keys( $this->columns );
		if ( ! in_array( $this->ID, $columns ) ) {
			$columns[] = $this->ID;
		}

		return $columns;
	}
	protected function get_items_query_search() {
		global $wpdb;

		if ( empty( $_GET['s'] ) || empty( $this->search_by ) ) {
			return '';
		}

		$filter  = array();
		foreach ( $this->search_by as $column ) {
			$filter[] = '`' . $column . '` like "%' . $wpdb->esc_like( $_GET['s'] ) . '%"';
		}
		return implode( ' OR ', $filter );
	}
	protected function get_items_query_filters() {
		global $wpdb;

		if ( ! $this->filter_by || empty( $_GET['filter_by'] ) || ! is_array( $_GET['filter_by'] ) ) {
			return '';
		}

		$filter = array();

		foreach ( $this->filter_by as $column => $options ) {
			if ( empty( $_GET['filter_by'][ $column ] ) || empty( $options[ $_GET['filter_by'][ $column ] ] ) ) {
				continue;
			}

			$filter[] = $wpdb->prepare( "`$column` = %s", $_GET['filter_by'][ $column ] );
		}

		return implode( ' AND ', $filter );

	}
	public function prepare_items() {
		global $wpdb;

		$this->process_bulk_action();

		$this->process_row_actions();

		if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
			// _wp_http_referer is used only on bulk actions, we remove it to keep the $_GET shorter
			wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
			exit;
		}

		$this->prepare_column_headers();

		$limit   = $this->get_items_query_limit();
		$offset  = $this->get_items_query_offset();
		$order   = $this->get_items_query_order();
		$where   = array_filter(array(
			$this->get_items_query_search(),
			$this->get_items_query_filters(),
		));
		$columns = '`' . implode( '`, `', $this->get_table_columns() ) . '`';

		if ( ! empty( $where ) ) {
			$where = 'WHERE ('. implode( ') AND (', $where ) . ')';
		} else {
			$where = '';
		}

		$sql = "SELECT $columns FROM {$this->table_name} {$where} {$order} {$limit} {$offset}";

		$this->set_items( $wpdb->get_results( $sql, ARRAY_A ) );

		$query_count = "SELECT COUNT({$this->ID}) FROM {$this->table_name} {$where}";
		$total_items = $wpdb->get_var( $query_count );
		$per_page    = $this->get_items_per_page( $this->package . '_items_per_page', $this->items_per_page );
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}

	public function extra_tablenav( $which ) {
		if ( ! $this->filter_by || 'top' !== $which ) {
			return;
		}

		echo '<div class="alignleft actions">';

		foreach ( $this->filter_by as $id => $options ) {
			$default = ! empty( $_GET['filter_by'][ $id ] ) ? $_GET['filter_by'][ $id ] : '';
			if ( empty( $options[ $default ] ) ) {
				$default = '';
			}

			echo '<select name="filter_by[' . esc_attr( $id ) . ']" class="first" id="filter-by-' . esc_attr( $id ) . '">';

			foreach ( $options as $value => $label ) {
				echo '<option value="' . esc_attr( $value ) . '" ' . esc_html( $value == $default ? 'selected' : '' )  .'>'
					. esc_html( $this->translate( $label ) )
				. '</option>';
			}

			echo '</select>';
		}

		submit_button( $this->translate( 'Filter' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
		echo '</div>';
	}
	protected function set_items( array $items ) {
		$this->items = array();
		foreach ( $items as $item ) {
			$this->items[ $item[ $this->ID ] ] = array_map( 'maybe_unserialize', $item );
		}
	}
	public function column_cb( $row ) {
		return '<input name="ID[]" type="checkbox" value="' . esc_attr( $row[ $this->ID ] ) .'" />';
	}
	protected function maybe_render_actions( $row, $column_name ) {
		if ( empty( $this->row_actions[ $column_name ] ) ) {
			return;
		}

		$row_id = $row[ $this->ID ];

		$actions = '<div class="row-actions">';
		$action_count = 0;
		foreach ( $this->row_actions[ $column_name ] as $action_key => $action ) {

			$action_count++;

			if ( ! method_exists( $this, 'row_action_' . $action_key ) ) {
				continue;
			}

			$action_link = ! empty( $action['link'] ) ? $action['link'] : add_query_arg( array( 'row_action' => $action_key, 'row_id' => $row_id, 'nonce'  => wp_create_nonce( $action_key . '::' . $row_id ) ) );
			$span_class  = ! empty( $action['class'] ) ? $action['class'] : $action_key;
			$separator   = ( $action_count < count( $this->row_actions[ $column_name ] ) ) ? ' | ' : '';

			$actions .= sprintf( '<span class="%s">', esc_attr( $span_class ) );
			$actions .= sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', esc_url( $action_link ), esc_attr( $action['desc'] ), esc_html( $action['name'] ) );
			$actions .= sprintf( '%s</span>', $separator );
		}
		$actions .= '</div>';
		return $actions;
	}

	protected function process_row_actions() {
		$parameters = array( 'row_action', 'row_id', 'nonce' );
		foreach ( $parameters as $parameter ) {
			if ( empty( $_REQUEST[ $parameter ] ) ) {
				return;
			}
		}

		$method = 'row_action_' . $_REQUEST['row_action'];

		if ( $_REQUEST['nonce'] === wp_create_nonce( $_REQUEST[ 'row_action' ] . '::' . $_REQUEST[ 'row_id' ] ) && method_exists( $this, $method ) ) {
			$this->$method( $_REQUEST['row_id'] );
		}

		wp_redirect( remove_query_arg(
			array( 'row_id', 'row_action', 'nonce' ),
			wp_unslash( $_SERVER['REQUEST_URI'] )
		) );
		exit;
	}
	public function column_default( $item, $column_name ) {
		$column_html = esc_html( $item[ $column_name ] );
		$column_html .= $this->maybe_render_actions( $item, $column_name );
		return $column_html;
	}
	protected function display_header() {
		echo '<h1 class="wp-heading-inline">' . esc_attr( $this->table_header ) . '</h1>';
		if ( $this->get_request_search_query() ) {
			echo '<span class="subtitle">' . esc_attr( $this->translate( sprintf( 'Search results for "%s"', $this->get_request_search_query() ) ) ) . '</span>';
		}
		echo '<hr class="wp-header-end">';
	}
	protected function display_admin_notices() {
		foreach ( $this->admin_notices as $notice ) {
			echo '<div id="message" class="' . $notice['class'] . '">';
			echo '	<p>' . wp_kses_post( $notice['message'] ) . '</p>';
			echo '</div>';
		}
	}
	protected function display_filter_by_status() {

		$status_list_items = array();
		$request_status    = $this->get_request_status();

		// Helper to set 'all' filter when not set on status counts passed in
		if ( ! isset( $this->status_counts['all'] ) ) {
			$this->status_counts = array( 'all' => array_sum( $this->status_counts ) ) + $this->status_counts;
		}

		foreach ( $this->status_counts as $status_name => $count ) {

			if ( 0 === $count ) {
				continue;
			}

			if ( $status_name === $request_status || ( empty( $request_status ) && 'all' === $status_name ) ) {
				$status_list_item = '<li class="%1$s"><strong>%3$s</strong> (%4$d)</li>';
			} else {
				$status_list_item = '<li class="%1$s"><a href="%2$s">%3$s</a> (%4$d)</li>';
			}

			$status_filter_url   = ( 'all' === $status_name ) ? remove_query_arg( 'status' ) : add_query_arg( 'status', $status_name );
			$status_list_items[] = sprintf( $status_list_item, esc_attr( $status_name ), esc_url( $status_filter_url ), esc_html( ucfirst( $status_name ) ), absint( $count ) );
		}

		if ( $status_list_items ) {
			echo '<ul class="subsubsub">';
			echo implode( " | \n", $status_list_items );
			echo '</ul>';
		}
	}
	protected function display_table() {
		echo '<form id="' . esc_attr( $this->_args['plural'] ) . '-filter" method="get">';
		foreach ( $_GET as $key => $value ) {
			if ( '_' === $key[0] || 'paged' === $key ) {
				continue;
			}
			echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
		}
		if ( ! empty( $this->search_by ) ) {
			echo $this->search_box( $this->get_search_box_button_text(), 'plugin' ); // WPCS: XSS OK
		}
		parent::display();
		echo '</form>';
	}
	public function display_page() {
		$this->prepare_items();

		echo '<div class="wrap">';
		$this->display_header();
		$this->display_admin_notices();
		$this->display_filter_by_status();
		$this->display_table();
		echo '</div>';
	}
	protected function get_search_box_placeholder() {
		return $this->translate( 'Search' );
	}
}
