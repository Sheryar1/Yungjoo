<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PAFW_Shortcodes' ) ) :

	class PAFW_Shortcodes {

		static $is_enqueued = false;

		static $pafw_params = array();

		public static function init() {
			$shortcodes = array(
				'pafw_instant_payment'  => array( __CLASS__, 'instant_payment' ),
				'pafw_personal_payment' => array( __CLASS__, 'personal_payment' ),
				'pafw_simple_payment'   => array( __CLASS__, 'simple_payment' ),
			);

			foreach ( $shortcodes as $shortcode => $function ) {
				add_shortcode( $shortcode, $function );
			}

			add_filter( 'msaddr_field_is_enabled', array( __CLASS__, 'maybe_field_is_enabled' ), 10, 2 );
		}

		public static function get_default_shortcode_params( $params = array() ) {
			return shortcode_atts( array(
				'uid'                 => uniqid( 'pafw_' ),
				'show_account'        => 'no',
				'account_header'      => __( '=== 결제 정보 확인 후 결제를 진행 해 주세요. ===', 'pgall-for-woocommerce' ),
				'show_payment_method' => 'no',
				'show_payment_field'  => 'no',
				'show_order_note'     => 'no',
				'show_terms'          => 'yes',
				'template'            => 'type1',

				'personal_payment'                   => 'no',
				'personal_payment_title_label'       => __( '정보입력', 'pgall-for-woocommerce' ),
				'personal_payment_title_placeholder' => __( '이름 또는 내용을 입력하세요.', 'pgall-for-woocommerce' ),
				'personal_payment_price_label'       => __( '금액', 'pgall-for-woocommerce' ),
				'personal_payment_price_placeholder' => __( '결제할 금액을 입력하세요.', 'pgall-for-woocommerce' ),
				'need_shipping'                      => 'no',
				'editable_account_info'              => 'no',
				'enable_guest_checkout'              => 'yes',
				'payment_method'                     => '',
				'billing_first_name'                 => '',
				'billing_phone'                      => '',
				'button_text'                        => __( '구매하기', 'pgall-for-woocommerce' ),
				'order_title'                        => '',
				'order_amount'                       => '',
				'product_id'                         => '0',
				'variation_id'                       => '',
				'variation'                          => '',
				'cart_item_data'                     => '',
				'quantity'                           => '1',
				'order_received_url'                 => '',
			), $params );
		}

		public static function instant_payment( $attrs, $content = null ) {
			define( 'PAFW_SIMPLE_PAYMENT', true );

			$params = self::get_default_shortcode_params( array(
				'show_payment_method' => 'yes',
				'show_payment_field'  => 'yes',
			) );

			if ( is_user_logged_in() ) {
				$customer                     = new WC_Customer( get_current_user_id() );
				$params['billing_first_name'] = $customer->get_billing_first_name();
				$params['billing_phone']      = $customer->get_billing_phone();
			}

			$params = shortcode_atts( $params, $attrs );

			self::$pafw_params = $params;

			if ( ! self::$is_enqueued ) {
				PAFW()->wp_enqueue_scripts( true );
				self::$is_enqueued = true;
			}

			ob_start();

			if ( 'no' == $params['enable_guest_checkout'] && ! is_user_logged_in() ) {
				wc_get_template( 'checkout/pafw/need-login.php', array(), '', PAFW()->template_path() );
			} else {
				wc_get_template( 'checkout/pafw/instant-payment.php', array( 'params' => $params, 'checkout' => WC()->checkout() ), '', PAFW()->template_path() );
			}

			return ob_get_clean();
		}


		public static function personal_payment( $attrs, $content = null ) {
			define( 'PAFW_SIMPLE_PAYMENT', true );

			$params = self::get_default_shortcode_params( array(
				'template'                           => 'type4',
				'show_account'                       => 'yes',
				'personal_payment'                   => 'yes',
				'show_terms'                         => 'no',
				'personal_payment_title_label'       => __( '정보입력', 'pgall-for-woocommerce' ),
				'personal_payment_title_placeholder' => __( '이름 또는 내용을 입력하세요.', 'pgall-for-woocommerce' ),
				'personal_payment_price_label'       => __( '금액', 'pgall-for-woocommerce' ),
				'personal_payment_price_placeholder' => __( '결제할 금액을 입력하세요.', 'pgall-for-woocommerce' ),
			) );

			if ( is_user_logged_in() ) {
				$customer                     = new WC_Customer( get_current_user_id() );
				$params['billing_first_name'] = $customer->get_billing_first_name();
				$params['billing_phone']      = $customer->get_billing_phone();
			}

			$params = shortcode_atts( $params, $attrs );

			self::$pafw_params = $params;

			if ( ! self::$is_enqueued ) {
				PAFW()->wp_enqueue_scripts( true );
				self::$is_enqueued = true;
			}

			ob_start();

			if ( 'no' == $params['enable_guest_checkout'] && ! is_user_logged_in() ) {
				wc_get_template( 'checkout/pafw/need-login.php', array(), '', PAFW()->template_path() );
			} else {
				wc_get_template( 'checkout/pafw/instant-payment.php', array( 'params' => $params, 'checkout' => WC()->checkout() ), '', PAFW()->template_path() );
			}

			return ob_get_clean();
		}
		public static function simple_payment( $attrs, $content = null ) {
			define( 'PAFW_SIMPLE_PAYMENT', true );

			$params = self::get_default_shortcode_params(
				array(
					'show_terms' => 'no'
				)
			);

			if ( is_user_logged_in() ) {
				$customer                     = new WC_Customer( get_current_user_id() );
				$params['billing_first_name'] = $customer->get_billing_first_name();
				$params['billing_phone']      = $customer->get_billing_phone();
			}

			$params = shortcode_atts( $params, $attrs );

			self::$pafw_params = $params;

			if ( ! self::$is_enqueued ) {
				PAFW()->wp_enqueue_scripts( true );
				self::$is_enqueued = true;
			}

			ob_start();

			if ( 'no' == $params['enable_guest_checkout'] && ! is_user_logged_in() ) {
				wc_get_template( 'checkout/pafw/need-login.php', array(), '', PAFW()->template_path() );
			} else {
				wc_get_template( 'checkout/pafw/instant-payment.php', array( 'params' => $params, 'checkout' => WC()->checkout() ), '', PAFW()->template_path() );
			}

			return ob_get_clean();
		}

		public static function maybe_field_is_enabled( $enabled, $field ) {
			static $address_fields = array(
				'mshop_billing_address',
				'billing_postcode',
				'billing_address_1',
				'billing_address_2',
				'billing_state',
				'billing_city',
				'mshop_shipping_address',
				'shipping_postcode',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_state',
				'shipping_city',
			);

			if ( defined( 'PAFW_SIMPLE_PAYMENT' ) && in_array( $field['id'], $address_fields ) ) {
				$enabled = pafw_get( self::$pafw_params, 'need_shipping' );
			}

			return $enabled;
		}
	}

	PAFW_Shortcodes::init();

endif;