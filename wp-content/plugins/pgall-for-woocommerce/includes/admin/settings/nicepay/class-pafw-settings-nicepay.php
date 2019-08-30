<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'NICEPAY_PG_Settings' ) ) {

	abstract class PAFW_Settings_Nicepay extends PAFW_Settings {

		public function __construct() {
			$this->master_id = 'nicepay';

			$this->prefix = '';

			parent::__construct();
		}
		function get_basic_setting_fields() {
			$instance = pafw_get_settings( 'nicepay_basic' );

			return $instance->get_setting_fields();
		}
		function get_advanced_setting_fields() {
			$instance = pafw_get_settings( 'nicepay_advanced' );

			return $instance->get_setting_fields();
		}
	}
}
