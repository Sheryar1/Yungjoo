<?php
add_action( 'pafw_process_payment', array ( 'PAFW_Session', 'process_payment' ) );
add_action( 'pafw_thankyou_page', array ( 'PAFW_Session', 'thankyou_page' ) );
add_action( 'pafw_payment_cancel', array ( 'PAFW_Session', 'payment_cancel' ) );
add_action( 'pafw_payment_fail', array ( 'PAFW_Session', 'payment_fail' ), 10, 3 );
add_action( 'woocommerce_before_checkout_form', array ( 'PAFW_Session', 'clear_session' ) );
add_action( 'pafw_cancel_unfinished_payment_request', array ( 'PAFW_Session', 'cancel_unfinished_payment_request' ) );
add_action( 'pafw_cancel_unfinished_payment_request', array ( 'PAFW_Gateway', 'update_statistics' ) );
add_action( 'pafw_payment_action', array ( 'PAFW_Gateway', 'payment_action' ), 10, 4 );
add_filter( 'woocommerce_get_sections_checkout', array ( 'WC_Gateway_PAFW_Inicis', 'checkout_sections' ) );
add_filter( 'woocommerce_get_sections_checkout', array ( 'WC_Gateway_PAFW_Nicepay', 'checkout_sections' ) );
add_filter( 'woocommerce_get_sections_checkout', array ( 'WC_Gateway_PAFW_Kcp', 'checkout_sections' ) );
add_filter( 'woocommerce_get_sections_checkout', array ( 'WC_Gateway_PAFW_LGUPlus', 'checkout_sections' ) );
add_filter( 'woocommerce_get_sections_checkout', array ( 'WC_Gateway_PAFW_Payco', 'checkout_sections' ) );
add_filter( 'woocommerce_get_sections_checkout', array ( 'WC_Gateway_PAFW_KakaoPay', 'checkout_sections' ) );

add_action( 'woocommerce_order_status_changed', array ( 'PAFW_Exchange_Return_Manager', 'woocommerce_order_status_changed' ), 10, 3 );
add_action( 'woocommerce_my_account_my_orders_actions', array ( 'PAFW_Exchange_Return_Manager', 'add_exchange_return_actions' ), 10, 2 );
add_action( 'wc_order_is_editable', array ( 'PAFW_Exchange_Return_Manager', 'pafw_order_is_editable' ), 10, 2 );
add_action( 'woocommerce_admin_order_items_after_line_items', 'PAFW_Meta_Box_Order_Items::output_exchange_return_request', 10, 3 );
add_filter( 'woocommerce_account_menu_items', 'PAFW_Bill_Key::add_account_menu_items' );
add_action( 'woocommerce_account_pafw-card_endpoint', 'PAFW_Bill_Key::card_info' );
add_action( 'woocommerce_account_pafw-card-register_endpoint', 'PAFW_Bill_Key::register_card' );

add_filter( 'pafw_payment_script_params', array ( 'WC_Gateway_Lguplus', 'add_script_params' ) );
add_filter( 'msex_get_additional_charge', array ( 'PAFW_Exporter', 'get_additional_charge' ), 10, 2 );
add_filter( 'msex_get_partial_refund', array ( 'PAFW_Exporter', 'get_partial_refund' ), 10, 2 );
add_filter( 'woocommerce_my_account_my_orders_actions', 'pafw_my_account_my_orders_actions', 10, 2 );
function pafw_my_account_my_orders_actions( $actions, $order ) {
	$payment_gateway = pafw_get_payment_gateway_from_order( $order );

	if ( $payment_gateway instanceof PAFW_Payment_Gateway ) {
		$actions = $payment_gateway->my_account_my_orders_actions( $actions, $order );
	}

	return $actions;
}
add_action( 'woocommerce_view_order', 'pafw_wc_action', 5 );
add_action( 'woocommerce_email_before_order_table', 'pafw_wc_action', 5 );
function pafw_wc_action( $order_id ) {
	$action = current_action();
	$order  = wc_get_order( $order_id );

	$payment_gateway = pafw_get_payment_gateway_from_order( $order );

	if ( $payment_gateway instanceof PAFW_Payment_Gateway && is_callable( array ( $payment_gateway, $action ) ) ) {
		$payment_gateway->$action( $order_id, $order );
	}
}
add_filter( 'woocommerce_payment_complete_order_status', 'pafw_woocommerce_payment_complete_order_status', 10, 3 );
function pafw_woocommerce_payment_complete_order_status( $order_status, $order_id, $order = null ) {
	if ( is_null( $order ) ) {
		$order = wc_get_order( $order_id );
	}

	$payment_gateway = pafw_get_payment_gateway_from_order( $order );

	if ( $payment_gateway instanceof PAFW_Payment_Gateway && is_callable( array ( $payment_gateway, 'woocommerce_payment_complete_order_status' ) ) ) {
		$order_status = $payment_gateway->woocommerce_payment_complete_order_status( $order_status, $order_id, $order );
	}

	return $order_status;
}
add_filter( 'woocommerce_get_checkout_order_received_url', 'pafw_woocommerce_get_checkout_order_received_url', 99, 2 );
function pafw_woocommerce_get_checkout_order_received_url( $url, $order ) {
	if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
		$payment_gateway = pafw_get_payment_gateway_from_order( $order );

		if ( $payment_gateway instanceof PAFW_Payment_Gateway ) {

			$checkout_pid = wc_get_page_id( 'checkout' );
			if ( ! empty( $_REQUEST['lang'] ) ) {
				if ( function_exists( 'icl_object_id' ) ) {
					$checkout_pid = icl_object_id( $checkout_pid, 'page', true, $_REQUEST['lang'] );
				}
			}

			$url = wc_get_endpoint_url( 'order-received', pafw_get_object_property( $order, 'id' ), get_permalink( $checkout_pid ) );

			if ( pafw_check_ssl() ) {
				$url = str_replace( 'http:', 'https:', $url );
			}

			$url = add_query_arg( 'key', pafw_get_object_property( $order, 'order_key' ), $url );
		}
	}

	return $url;
}

add_action( 'init', 'pafw_cancel_order' );
function pafw_cancel_order() {

	if ( isset( $_GET['pafw-cancel-order'] ) && isset( $_GET['order_key'] ) && isset( $_GET['order_id'] ) ) {

		$order_id  = $_GET['order_id'];
		$order_key = $_GET['order_key'];

		$order = wc_get_order( $_GET['order_id'] );

		// Order or payment link is invalid.
		if ( $order && pafw_get_object_property( $order, 'order_key' ) == $order_key ) {

			$customer_id = is_callable( array ( $order, 'get_customer_id' ) ) ? $order->get_customer_id() : $order->customer_user;

			if ( $customer_id == get_current_user_id() ) {
				$payment_gateway = pafw_get_payment_gateway_from_order( $order );

				if ( $payment_gateway instanceof PAFW_Payment_Gateway ) {
					$payment_gateway->cancel_order( $order );
				}
			}
		}

		if ( empty( $_GET['redirect'] ) ) {
			echo '<meta http-equiv="refresh" content="0; url=' . get_permalink( wc_get_page_id( 'myaccount' ) ) . '" />';
			wp_safe_redirect( get_permalink( wc_get_page_id( 'myaccount' ) ), 301 );
		} else {
			echo '<meta http-equiv="refresh" content="0; url=' . wc_clean( $_GET['redirect'] ) . '" />';
			wp_safe_redirect( wc_clean( $_GET['redirect'] ), 301 );
		}
		die();
	}
}
function pafw_get( $array, $key, $default = '' ) {
	return ! empty( $array[ $key ] ) ? $array[ $key ] : $default;
}

function pafw_get_object_property( $object, $property ) {
	$method = 'get_' . $property;

	return is_callable( array ( $object, $method ) ) ? $object->$method() : $object->$property;
}
function pafw_get_payment_gateway_from_order( $order ) {

	if ( $order ) {
		$payment_method = pafw_get_object_property( $order, 'payment_method' );

		return pafw_get_payment_gateway( $payment_method );
	}

	return null;
}
function pafw_get_payment_gateway( $payment_method ) {
	$class              = 'WC_Gateway_' . ucwords( $payment_method, '_' );
	$available_gateways = WC()->payment_gateways()->payment_gateways();

	if ( ! empty( $available_gateways[ $payment_method ] ) ) {
		return $available_gateways[ $payment_method ];
	} else if ( class_exists( $class, true ) ) {
		return new $class;
	}

	return null;
}
function pafw_get_settings( $id ) {
	$class = 'PAFW_Settings_' . ucwords( $id, '_' );

	if ( class_exists( $class, true ) ) {
		return new $class;
	}

	return null;
}
function pafw_reduce_order_stock( $order ) {

	if ( ! empty( $order ) ) {
		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			$order->reduce_order_stock();
		} else {
			//재고 차감 여부 확인 후, 재고 조정 처리 진행
			if ( ! $order->get_data_store()->get_stock_reduced( $order->get_id() ) ) {
				wc_reduce_stock_levels( $order->get_id() );
			}

		}
	}

}
function pafw_get_meta( $object, $meta_key, $single = true, $context = 'view' ) {
	if ( is_callable( array ( $object, 'get_meta' ) ) ) {
		return $object->get_meta( $meta_key, $single, $context );
	} else {
		if ( $object instanceof WC_Abstract_Order ) {
			return get_post_meta( $object->id, $meta_key, $single );
		} else if ( is_numeric( $object ) ) {
			return wc_get_order_item_meta( $object, $meta_key, $single );
		} else if ( $object instanceof WC_Order_Item ) {
			return wc_get_order_item_meta( $object->id, $meta_key, $single );
		}
	}
}
function pafw_update_meta_data( $object, $key, $value ) {
	if ( is_callable( array ( $object, 'update_meta_data' ) ) ) {
		$object->update_meta_data( $key, $value );
		$object->save();
	} else {
		if ( $object instanceof WC_Abstract_Order ) {
			update_post_meta( $object->id, $key, $value );
		} else if ( $object instanceof WC_Order_Item ) {
			wc_update_order_item_meta( $object->id, $key, $value );
		} else if ( is_numeric( $object ) ) {
			wc_update_order_item_meta( $object, $key, $value );
		}
	}
}
function pafw_delete_meta_data( $object, $meta_key ) {
	if ( is_callable( array ( $object, 'delete_meta_data' ) ) ) {
		$object->delete_meta_data( $meta_key );
		$object->save();
	} else {
		delete_post_meta( pafw_get_object_property( $object, 'id' ), $meta_key );
	}
}
function pafw_set_browser_information( $order ) {
	pafw_update_meta_data( $order, '_pafw_device_type', wp_is_mobile() ? __( 'MOBILE', 'pgall-for-woocommerce' ) : __( 'PC', 'pgall-for-woocommerce' ) );
}

function pafw_check_ssl() {
	return apply_filters( 'pafw_check_ssl', is_ssl() || 'yes' == get_option( 'woocommerce_force_ssl_checkout' ) );
}

add_action( 'woocommerce_settings_start', 'pafw_woocommerce_settings_start' );
function pafw_woocommerce_settings_start() {
	add_filter( 'admin_url', 'pafw_admin_url', 10, 3 );
}

function pafw_admin_url( $url, $path, $blog_id ) {
	$supported_gateways = PAFW()->get_supported_gateways();

	foreach ( $supported_gateways as $gateway ) {
		$pos = strpos( $url, 'section=' . $gateway . '_' );
		if ( $pos !== false ) {
			$url = substr( $url, 0, $pos ) . 'section=mshop_' . $gateway;

			return $url;
		}
	}

	return $url;
}
function pafw_convert_to_utf8( $str ) {
	if ( 'EUC-KR' == mb_detect_encoding( $str, array ( 'UTF-8', 'EUC-KR' ) ) ) {
		$str = mb_convert_encoding( $str, 'UTF-8', 'EUC-KR' );
	}

	return $str;
}
add_action( 'woocommerce_checkout_order_processed', array ( 'PAFW_Review', 'save_review_info' ), 10, 3 );
add_action( 'woocommerce_order_status_changed', array ( 'PAFW_Review', 'register_review' ), 10, 3 );
if ( ! function_exists( 'pafw_get_default_language_args' ) ) {
	function pafw_get_default_language_args() {
		if ( function_exists( 'icl_object_id' ) ) {
			return 'lang=' . ICL_LANGUAGE_CODE . '&';
		} else {
			return '';
		}
	}
}

if ( ! function_exists( 'pafw_get_default_language' ) ) {
	function pafw_get_default_language() {
		if ( function_exists( 'icl_object_id' ) ) {
			global $sitepress;
			return $sitepress->get_default_language();
		} else {
			return '';
		}
	}
}


add_filter( 'woocommerce_available_payment_gateways', array ( 'PAFW_Payment_Method_Controller', 'filter_available_payment_gateways' ), 10, 2 );
add_filter( 'woocommerce_add_to_cart_validation', array ( 'PAFW_Payment_Method_Controller', 'woocommerce_add_to_cart_validation' ), 10, 5 );
add_filter( 'msm_submit_action', 'PAFW_MShop_Members::submit_action' );
add_filter( 'msm_form_classes', 'PAFW_MShop_Members::add_form_classes', 10, 2 );
add_filter( 'mfd_output_forms_pafw_payment', 'PAFW_MShop_Members::output_unique_id' );
add_filter( 'msm_get_field_rules', 'PAFW_MShop_Members::add_field_rules', 10, 2 );

function pafw_get_customer_info() {
	$customer_info = array ();

	if ( is_user_logged_in() ) {
		try {
			$user = new WC_Customer( get_current_user_id() );

			if ( $user ) {
				$customer_info = array (
					'name'     => $user->get_billing_last_name() . $user->get_billing_first_name(),
					'phone'    => $user->get_billing_phone(),
					'email'    => $user->get_billing_email(),
					'postcode' => $user->get_billing_postcode(),
					'address1' => $user->get_billing_address_1(),
					'address2' => $user->get_billing_address_2()
				);
			}
		} catch ( Exception $e ) {

		}
	}

	return apply_filters( 'pafw_get_customer_info', $customer_info );
}
function pafw_get_customer_phone_number( $order, $user_id = 0 ) {

	if ( $order ) {
		$phone_number = preg_replace( "/[^0-9]*/s", "", pafw_get_object_property( $order, 'billing_phone' ) );
	} else {
		$phone_number = preg_replace( "/[^0-9]*/s", "", get_user_meta( $user_id, 'billing_phone', true ) );
	}

	return apply_filters( 'pafw_get_customer_phone_number', $phone_number, $order );
}