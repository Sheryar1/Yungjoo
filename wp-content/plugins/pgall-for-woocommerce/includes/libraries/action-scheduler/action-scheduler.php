<?php

if ( ! function_exists( 'action_scheduler_register_2_dot_1_dot_0' ) ) {

	if ( ! class_exists( 'ActionScheduler_Versions' ) ) {
		require_once( 'classes/ActionScheduler_Versions.php' );
		add_action( 'plugins_loaded', array( 'ActionScheduler_Versions', 'initialize_latest_version' ), 1, 0 );
	}

	add_action( 'plugins_loaded', 'action_scheduler_register_2_dot_1_dot_0', 0, 0 );

	function action_scheduler_register_2_dot_1_dot_0() {
		$versions = ActionScheduler_Versions::instance();
		$versions->register( '2.1.0', 'action_scheduler_initialize_2_dot_1_dot_0' );
	}

	function action_scheduler_initialize_2_dot_1_dot_0() {
		require_once( 'classes/ActionScheduler.php' );
		ActionScheduler::init( __FILE__ );
	}

}
