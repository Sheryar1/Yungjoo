<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_PAFW_KakaoPay' ) ) {

	include_once( 'class-wc-gateway-pafw.php' );
	class WC_Gateway_PAFW_KakaoPay extends WC_Gateway_PAFW {
		public function __construct() {
			$this->id = 'mshop_kakaopay';

			$this->init_settings();
			$this->method_title = __( '카카오페이', 'pgall-for-woocommerce' );

			$this->title              = __( '카카오페이', 'pgall-for-woocommerce' );
			$this->method_title       = __( '카카오페이', 'pgall-for-woocommerce' );
			$this->method_description = '<div style="font-size: 0.9em;">카카오페이 간편결제 및 정기결제를 이용합니다.</div>';

			parent::__construct();
		}
		public static function get_supported_payment_methods() {
			return array (
				'kakaopay_simplepay' => '카카오페이 간편결제',
				'kakaopay_subscription' => '카카오페이 정기결제',
			);
		}
		public function admin_options() {

			parent::admin_options();

			$options = get_option( 'pafw_mshop_kakaopay' );

			$GLOBALS['hide_save_button'] = 'yes' != pafw_get( $options, 'show_save_button', 'no' );

			$settings = $this->get_settings( 'kakaopay', self::get_supported_payment_methods() );

			$this->enqueue_script();
			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array (
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => PAFW()->slug() . '-update_kakaopay_settings',
				'settings' => $settings
			) );

			?>
            <script>
                jQuery(document).ready(function($) {
                    $(this).trigger('mshop-setting-manager', ['mshop-setting-wrapper', '600', <?php echo json_encode( $this->get_setting_values( $this->id, $settings ) ); ?>, null, null]);
                });
            </script>

            <div id="mshop-setting-wrapper"></div>
			<?php

		}

		protected function get_key() {
			return pafw_get( $_REQUEST, 'cid' );
		}

		protected function valid_keys() {
			return array (
				array (
					'length' => 10,
					'value'  => 'VEMwT05FVElNRQ=='
				),
				array (
					'length' => 4,
					'value'  => 'QzUwMg=='
				),
				array (
					'length' => 2,
					'value'  => 'Q0Q='
				)
			);
		}

		protected function invalid_key_message() {
			return __( '유효하지 않은 가맹점코드(CID) 입니다. 가맹점코드는 "CM"으로 시작되어야 합니다.', 'pgall-for-woocommerce' );
		}
	}
}