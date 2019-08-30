<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
add_action( 'woocommerce_account_pafw-ex_endpoint', 'pafw_exchange_return_request' );
add_action( 'woocommerce_order_details_after_order_table', 'pafw_show_payment_details', 1 );
add_action( 'woocommerce_order_details_after_order_table', 'pafw_exchange_return_details' );

add_action( 'pafw_exchange_return_request', 'pafw_exchange_return_type', 10 );
add_action( 'pafw_exchange_return_request', 'pafw_exchange_return_items', 20 );
add_action( 'pafw_exchange_return_request', 'pafw_exchange_return_reason', 30 );
add_action( 'pafw_exchange_return_request', 'pafw_exchange_return_bank_account', 40 );
add_action( 'pafw_exchange_return_request', 'pafw_exchange_return_action', 50 );

add_action( 'woocommerce_checkout_after_order_review', 'pafw_smart_review_form' );

add_action( 'woocommerce_track_order', 'pafw_exchange_return_for_track_order', 100 );