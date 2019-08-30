<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'pafw_exchange_return_details' ) ) {
	function pafw_exchange_return_details( $order ) {

		if ( $exchange_returns = PAFW_Exchange_Return_Manager::get_exchange_return_orders( $order ) ) {

			wp_enqueue_style( 'pafw-frontend', PAFW()->plugin_url() . '/assets/css/frontend.css' );

			wc_get_template( 'order/exchange-return-details.php', array ( 'exchange_returns' => $exchange_returns ), '', PAFW()->template_path() );

		}
	}
}

if ( ! function_exists( 'pafw_show_payment_details' ) ) {
	function pafw_show_payment_details( $order ) {

		$payment_gateway = pafw_get_payment_gateway_from_order( $order );

		if ( $payment_gateway && $payment_gateway->supports( 'pafw' ) ) {

			$tid         = $payment_gateway->get_transaction_id( $order );
			$receipt_url = $payment_gateway->get_transaction_url( $order );

			if ( empty( $tid ) || empty( $receipt_url ) ) {
				return;
			}

			wp_enqueue_style( 'pafw-frontend', PAFW()->plugin_url() . '/assets/css/frontend.css' );
			wc_get_template( 'order/show-payment-details.php', array ( 'order' => $order ), '', PAFW()->template_path() );
		}
	}
}
if ( ! function_exists( 'pafw_exchange_return_request' ) ) {
	function pafw_exchange_return_request() {
		wc_get_template( 'myaccount/exchange-return-request.php', array (), '', PAFW()->template_path() );
	}
}
if ( ! function_exists( 'pafw_exchange_return_type' ) ) {
	function pafw_exchange_return_type( $order_id ) {
		wc_get_template( 'myaccount/exchange-return-type.php', array ( 'order_id' => $order_id ), '', PAFW()->template_path() );
	}
}
if ( ! function_exists( 'pafw_exchange_return_items' ) ) {
	function pafw_exchange_return_items( $order_id ) {
		wc_get_template( 'myaccount/exchange-return-items.php', array ( 'order_id' => $order_id ), '', PAFW()->template_path() );
	}
}
if ( ! function_exists( 'pafw_exchange_return_reason' ) ) {
	function pafw_exchange_return_reason( $order_id ) {
		wc_get_template( 'myaccount/exchange-return-reason.php', array ( 'order_id' => $order_id ), '', PAFW()->template_path() );
	}
}
if ( ! function_exists( 'pafw_exchange_return_bank_account' ) ) {
	function pafw_exchange_return_bank_account( $order_id ) {
		wc_get_template( 'myaccount/exchange-return-bank-account.php', array ( 'order_id' => $order_id ), '', PAFW()->template_path() );
	}
}
if ( ! function_exists( 'pafw_exchange_return_action' ) ) {
	function pafw_exchange_return_action( $order_id ) {
		wc_get_template( 'myaccount/exchange-return-action.php', array ( 'order_id' => $order_id ), '', PAFW()->template_path() );
	}
}
if ( ! function_exists( 'pafw_smart_review_form' ) ) {
	function pafw_smart_review_form() {
		if ( 'yes' === get_option( 'woocommerce_enable_reviews', 'yes' ) && 'yes' == get_option( 'pafw-use-smart-review', 'no' ) ) {

			if ( get_option( 'comment_registration' ) && ! is_user_logged_in() ) {
				return;
			}

			$rate_options = get_option( 'pafw-smart-review-rate', array () );

			if ( empty( $rate_options ) ) {
				return;
			}

			wc_get_template( 'checkout/smart-review-' . get_option( 'pafw-smart-review-template', 'type1' ) . '.php', array ( 'rate_options' => $rate_options ), '', PAFW()->template_path() );
		}
	}
}
if ( ! function_exists( 'pafw_exchange_return_for_track_order' ) ) {
	function pafw_exchange_return_for_track_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order && PAFW_Exchange_Return_Manager::support_exchange_return() && PAFW_Exchange_Return_Manager::can_exchange_return( $order ) ) {
			add_action( 'woocommerce_view_order', 'pafw_output_exchange_return_for_track_order', 10 );
		}
	}
}

if ( ! function_exists( 'pafw_output_exchange_return_for_track_order' ) ) {
	function pafw_output_exchange_return_for_track_order( $order_id ) {
		global $wp;
		$wp->query_vars['pafw-ex'] = $order_id;

		wc_get_template( 'myaccount/exchange-return-request-for-track-order.php', array(), '', PAFW()->template_path() );
	}
}

