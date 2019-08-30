<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Inicis' ) ) {
	abstract class PAFW_Settings_Inicis extends PAFW_Settings {

		public function __construct() {
			$this->master_id = 'inicis';

			$this->prefix = '';

			parent::__construct();
		}
		function get_basic_setting_fields() {
			$instance = pafw_get_settings( 'inicis_basic' );

			return $instance->get_setting_fields();
		}
		function get_advanced_setting_fields() {
			$instance = pafw_get_settings( 'inicis_advanced' );

			return $instance->get_setting_fields();
		}
		function get_keyfile_list() {
			$options = get_option( 'pafw_mshop_inicis' );

			if( ! empty( $options) ) {
				$library_path = pafw_get( $options, 'libfolder' );
			}

			if ( empty( $library_path ) ) {
				$library_path = WP_CONTENT_DIR . '/inicis';
			}

			$dirs = glob( $library_path . '/key/*', GLOB_ONLYDIR );
			if ( count( $dirs ) > 0 ) {
				$result = array ();
				foreach ( $dirs as $val ) {
					if ( file_exists( $val . '/keypass.enc' ) && file_exists( $val . '/mcert.pem' ) && file_exists( $val . '/mpriv.pem' ) && file_exists( $val . '/readme.txt' ) ) {
						$result[ basename( $val ) ] = basename( $val );
					}
				}

				return $result;
			} else {
				return array ( - 1 => __( '=== 키파일을 업로드 해주세요 ===', 'pgall-for-woocommerce' ) );
			}
		}
	}
}
