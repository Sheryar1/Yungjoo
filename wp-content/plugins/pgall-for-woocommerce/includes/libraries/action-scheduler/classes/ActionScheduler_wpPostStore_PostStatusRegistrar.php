<?php
class ActionScheduler_wpPostStore_PostStatusRegistrar {
	public function register() {
		register_post_status( ActionScheduler_Store::STATUS_RUNNING, array_merge( $this->post_status_args(), $this->post_status_running_labels() ) );
		register_post_status( ActionScheduler_Store::STATUS_FAILED, array_merge( $this->post_status_args(), $this->post_status_failed_labels() ) );
	}
	protected function post_status_args() {
		$args = array(
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
		);

		return apply_filters( 'action_scheduler_post_status_args', $args );
	}
	protected function post_status_failed_labels() {
		$labels = array(
			'label'       => _x( 'Failed', 'post' ),
			'label_count' => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>' ),
		);

		return apply_filters( 'action_scheduler_post_status_failed_labels', $labels );
	}
	protected function post_status_running_labels() {
		$labels = array(
			'label'       => _x( 'In-Progress', 'post' ),
			'label_count' => _n_noop( 'In-Progress <span class="count">(%s)</span>', 'In-Progress <span class="count">(%s)</span>' ),
		);

		return apply_filters( 'action_scheduler_post_status_running_labels', $labels );
	}
}
 