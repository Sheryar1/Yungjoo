<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Bill_Key' ) ) {
	class PAFW_Bill_Key {
		protected static $payment_gateways = null;

		public static function get_payment_gateway() {
			if ( is_null( self::$payment_gateways ) ) {
				self::$payment_gateways = false;
				foreach ( WC()->payment_gateways()->payment_gateways() as $payment_gateway ) {
					if ( 'yes' == $payment_gateway->enabled && $payment_gateway->supports( 'pafw_bill_key_management' ) && 'user' == pafw_get( $payment_gateway->settings, 'management_batch_key', 'subscription' ) ) {
						self::$payment_gateways = $payment_gateway;
						break;
					}
				}
			}

			return self::$payment_gateways;
		}

		public static function add_account_menu_items( $items ) {

			if ( self::get_payment_gateway() ) {

				$items = array_merge(
					$items,
					array ( 'pafw-card' => __( '카드정보', 'pgall-for-woocommerce' ) )
				);
			}

			return $items;
		}

		public static function card_info() {
			$payment_gateway = self::get_payment_gateway();

			if ( $payment_gateway ) {
				$bill_key = get_user_meta( get_current_user_id(), '_pafw_bill_key', true );

				if ( empty ( $bill_key ) ) {
					self::register_card();
				} else {
					wp_enqueue_script( 'pafw-bill-key', PAFW()->plugin_url() . '/assets/js/bill-key.js', array ( 'jquery' ), PAFW_VERSION );
					wp_localize_script( 'pafw-bill-key', '_pafw_bill_key', array (
						'ajaxurl'        => admin_url( 'admin-ajax.php' ),
						'slug'           => PAFW()->slug(),
						'payment_method' => $payment_gateway->id,
						'_wpnonce'       => wp_create_nonce( 'pgall-for-woocommerce' )
					) );

					wc_get_template( 'pafw/' . $payment_gateway->master_id . '/card-info.php', array ( 'payment_gateway' => $payment_gateway ), '', PAFW()->template_path() );
				}
			}
		}

		public static function register_card() {
			$payment_gateway = self::get_payment_gateway();

			if ( $payment_gateway ) {
				wp_enqueue_script( 'pafw-bill-key', PAFW()->plugin_url() . '/assets/js/bill-key.js', array ( 'jquery' ), PAFW_VERSION );
				wp_localize_script( 'pafw-bill-key', '_pafw_bill_key', array (
					'ajaxurl'  => admin_url( 'admin-ajax.php' ),
					'slug'     => PAFW()->slug(),
					'payment_method' => $payment_gateway->id,
					'_wpnonce' => wp_create_nonce( 'pgall-for-woocommerce' )
				) );

				wc_get_template( 'pafw/' . $payment_gateway->master_id . '/card-register.php', array ( 'payment_gateway' => $payment_gateway ), '', PAFW()->template_path() );

			}
		}
	}

}