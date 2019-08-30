<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_PAFW_LGUPlus' ) ) {

	include_once( 'class-wc-gateway-pafw.php' );
	class WC_Gateway_PAFW_LGUPlus extends WC_Gateway_PAFW {
		public function __construct() {
			$this->id = 'mshop_lguplus';

			$this->init_settings();

			$this->title              = __( 'LG유플러스', 'pgall-for-woocommerce' );
			$this->method_title       = __( 'LG유플러스', 'pgall-for-woocommerce' );
			$this->method_description = '<div style="font-size: 0.9em;">LG 유플러스 일반결제를 이용합니다. (신용카드, 실시간 계좌이체, 가상계좌, 에스크로)</div>';

			parent::__construct();

		}
		public static function get_supported_payment_methods() {
			return array (
				'lguplus_card'        => '신용카드',
				'lguplus_bank'        => '실시간 계좌이체',
				'lguplus_vbank'       => '가상계좌',
				'lguplus_escrow_bank' => '에스크로',
			);
		}
		public function admin_options() {

			parent::admin_options();

			$options = get_option( 'pafw_mshop_lguplus' );

			$GLOBALS['hide_save_button'] = 'yes' != pafw_get( $options, 'show_save_button', 'no' );

			$settings                    = $this->get_settings( 'lguplus', self::get_supported_payment_methods() );

			$this->enqueue_script();
			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array (
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => PAFW()->slug() . '-update_lguplus_settings',
				'settings' => $settings
			) );

			?>
            <script>
				jQuery( document ).ready( function ( $ ) {
					$( this ).trigger( 'mshop-setting-manager', ['mshop-setting-wrapper', '200', <?php echo json_encode( $this->get_setting_values( $this->id, $settings ) ); ?>, null, null] );
				} );
            </script>
            <div id="mshop-setting-wrapper"></div>
			<?php
		}

		protected function get_key() {
			return pafw_get( $_REQUEST, 'merchant_id' );
		}

		protected function valid_keys() {
			return array (
				array (
					'length' => 11,
					'value'  => 'bGdkYWNvbXhwYXk='
				),
				array (
					'length' => 4,
					'value'  => 'Q0RNXw=='
				)
			);
		}

		protected function invalid_key_message() {
			return __( '유효하지 않은 상점 아이디 입니다. 상점 아이디는 "CDM_"로 시작되어야 합니다.', 'pgall-for-woocommerce' );
		}
	}
}