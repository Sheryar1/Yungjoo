<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_PAFW_Nicepay' ) ) {

	include_once( 'class-wc-gateway-pafw.php' );
	class WC_Gateway_PAFW_Nicepay extends WC_Gateway_PAFW {
		public function __construct() {
			$this->id = 'mshop_nicepay';

			$this->init_settings();

			$this->title              = __( '나이스페이', 'pgall-for-woocommerce' );
			$this->method_title       = __( '나이스페이', 'pgall-for-woocommerce' );
			$this->method_description = '<div style="font-size: 0.9em;">나이스페이 일반결제 및 정기결제를 이용합니다. (신용카드, 실시간 계좌이체, 가상계좌, 에스크로, 정기결제)</div>';

			parent::__construct();
		}
		public static function get_supported_payment_methods() {
			$payment_method = array (
				'nicepay_card'         => '신용카드',
				'nicepay_bank'         => '실시간 계좌이체',
				'nicepay_vbank'        => '가상계좌',
				'nicepay_escrow_bank'  => '에스크로'
			);

			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( ! is_plugin_active( 'mshop-subscription-for-woocommerce/mshop-subscription-for-woocommerce.php' ) ) {
			    $payment_method['nicepay_subscription'] = '정기결제';
			}

			return $payment_method;
		}
		public function admin_options() {

			parent::admin_options();

			$options = get_option( 'pafw_mshop_nicepay' );

			$GLOBALS['hide_save_button'] = 'yes' != pafw_get( $options, 'show_save_button', 'no' );

			$settings = $this->get_settings( 'nicepay', self::get_supported_payment_methods() );

			$this->enqueue_script();
			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array (
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => PAFW()->slug() . '-update_nicepay_settings',
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
					'length' => 10,
					'value'  => 'bmljZXBheTAwbQ=='
				),
				array (
					'length' => 3,
					'value'  => 'Y2Rt'
				)
			);
		}

		protected function invalid_key_message() {
			return __( '유효하지 않은 상점 아이디 입니다. 상점 아이디는 "cdm"으로 시작되어야 합니다.', 'pgall-for-woocommerce' );
		}
	}
}