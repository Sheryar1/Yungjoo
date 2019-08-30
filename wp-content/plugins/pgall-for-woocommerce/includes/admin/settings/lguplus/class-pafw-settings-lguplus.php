<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Lguplus' ) ) {

	abstract class PAFW_Settings_Lguplus extends PAFW_Settings {

		public function __construct() {
			$this->master_id = 'lguplus';

			$this->prefix = '';

			parent::__construct();
		}
		function get_basic_setting_fields() {
			$instance = pafw_get_settings( 'lguplus_basic' );

			return $instance->get_setting_fields();
		}
	}
}
