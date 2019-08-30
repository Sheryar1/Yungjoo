<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class PAFW_Meta_Box_Order_Items {

	public static function output_exchange_return_request( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $exchange_returns = PAFW_Exchange_Return_Manager::get_exchange_return_orders( $order ) ) {

			wp_enqueue_script( 'pafw-meta-box-order', PAFW()->plugin_url() . '/assets/js/admin/meta-boxes-order.js' );
			wp_localize_script( 'pafw-meta-box-order', '_pafw_order', array (
				'action_apply_exchange' => PAFW()->slug() . '-apply_exchange',
				'action_apply_return'   => PAFW()->slug() . '-apply_return'
			) );

			foreach ( $exchange_returns as $exchange_return ) {
				include( 'views/html-order-exchange-return.php' );
			}

		}
	}

}
