<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PAFW_Simple_Pay' ) ) :

	class PAFW_Order {
		protected $id = null;
		protected $title = '';
		protected $amount = 0;
		protected $payment_method = '';
		protected $billing_last_name = '홍길동';
		protected $billing_first_name = '';
		protected $billing_phone = '010-3333-3333';
		protected $billing_email = 'a@a.com';

		public function __construct( $title, $amount ) {
			$this->title  = $title;
			$this->amount = $amount;
		}

		public function get_title() {
			return $this->title;
		}

		public function get_items() {
			return array ();
		}

		public function get_total() {
			return $this->amount;
		}

		public function set_payment_method( $payment_method ) {
			$this->payment_method = $payment_method;
		}

		public function save() {
			set_transient( $this->get_id(), serialize( $this ) );
		}

		public function get_billing_last_name() {
			return $this->billing_last_name;
		}

		public function get_billing_first_name() {
			return $this->billing_first_name;
		}

		public function get_billing_phone() {
			return $this->billing_phone;
		}

		public function get_billing_email() {
			return $this->billing_email;
		}

		public function get_id() {
			if ( is_null( $this->id ) ) {
				$this->id = 'SP' . strtoupper( bin2hex( openssl_random_pseudo_bytes( 6 ) ) );
			}

			return $this->id;
		}
	}

	class PAFW_Simple_Pay {

		public static function init() {
//			add_filter( 'pafw_get_order', array ( __CLASS__, 'get_order' ), 10, 2 );
			add_filter( 'pafw_product_info', array ( __CLASS__, 'product_info' ), 10, 2 );
			add_filter( 'pafw_redirect_url', array ( __CLASS__, 'redirect_url' ), 10, 2 );
		}

		public static function get_order( $order, $order_id = null ) {
			if ( is_null( $order_id ) ) {
				if ( 'yes' == pafw_get( $_REQUEST, 'simple_pay' ) ) {
					$title  = $_REQUEST['order_title'];
					$amount = $_REQUEST['order_amount'];
					$order  = new PAFW_Order( $title, $amount );

					$wc_order = self::create_order();
				}
			} else if ( false !== strpos( $order_id, 'SP' ) ) {
				$order = maybe_unserialize( get_transient( $order_id ) );
			}

			return $order;
		}

		public static function product_info( $product_info, $order ) {
			if ( is_a( $order, 'PAFW_Order' ) ) {
				$product_info = $order->get_title();
			}

			return $product_info;
		}

		protected static function process_customer( $data ) {
			if ( 'no' == $_REQUEST['need_shipping'] ) {
				add_filter( 'msaddr_process_billing', '__return_false' );
				add_filter( 'msaddr_process_shipping', '__return_false' );
			}

			$customer_id = apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() );

			$customer = new WC_Customer( $customer_id );

			if ( ! empty( $data['billing_first_name'] ) ) {
				$customer->set_first_name( $data['billing_first_name'] );
			}

			if ( ! empty( $data['billing_last_name'] ) ) {
				$customer->set_last_name( $data['billing_last_name'] );
			}

			// If the display name is an email, update to the user's full name.
			if ( is_email( $customer->get_display_name() ) ) {
				$customer->set_display_name( $data['billing_first_name'] . ' ' . $data['billing_last_name'] );
			}

			foreach ( $data as $key => $value ) {
				// Use setters where available.
				if ( is_callable( array ( $customer, "set_{$key}" ) ) ) {
					$customer->{"set_{$key}"}( $value );

					// Store custom fields prefixed with wither shipping_ or billing_.
				} elseif ( 0 === stripos( $key, 'billing_' ) || 0 === stripos( $key, 'shipping_' ) ) {
					$customer->update_meta_data( $key, $value );
				}
			}
			do_action( 'woocommerce_checkout_update_customer', $customer, $data );

			$customer->save();

			do_action( 'woocommerce_checkout_update_user_meta', $customer_id, $data );

			if ( 'no' == $_REQUEST['need_shipping'] ) {
				remove_filter( 'msaddr_process_billing', '__return_false' );
				remove_filter( 'msaddr_process_shipping', '__return_false' );
			}
		}
		public static function validate_params( $data ) {
			if ( empty( $_REQUEST['product_id'] ) ) {
				if ( empty( $_REQUEST['order_title'] ) ) {
					throw new Exception( __( '결제 정보를 입력해주세요.', 'pgall-for-woocommerce' ) );
				}
				if ( empty( $_REQUEST['order_amount'] ) ) {
					throw new Exception( __( '결제 정보를 입력해주세요.', 'pgall-for-woocommerce' ) );
				}
			}
		}
		public static function create_order( $data, $order ) {
			try {
				self::validate_params( $data );

				if ( $order ) {
					$order->remove_order_items();
					$order_id = self::update_order( $data, $order );
				} else {
					$order_id = self::create_simple_pay_order( $data );
				}

				$order = wc_get_order( $order_id );

				$order_total = 0;

				$variation      = array ();
				$cart_item_data = array ();

				if ( ! empty( $_POST['variation'] ) ) {
					parse_str( $_POST['variation'], $variation );
				}

				if ( ! empty( $_POST['cart_item_data'] ) ) {
					parse_str( $_POST['cart_item_data'], $cart_item_data );
				}

				if ( empty( $_REQUEST['product_id'] ) ) {
					$item = new WC_Order_Item_Product();
					$item->set_props( array (
						'name'         => $_REQUEST['order_title'],
						'quantity'     => $_REQUEST['quantity'],
						'variation'    => array (),
						'subtotal'     => $_REQUEST['order_amount'],
						'total'        => $_REQUEST['order_amount'],
						'subtotal_tax' => 0,
						'total_tax'    => 0,
						'taxes'        => 0,
					) );
					$order->add_item( $item );

					$order_total = $_REQUEST['order_amount'];

					$order->set_total( $order_total );

					$order->save();
				} else {
					WC()->cart->empty_cart();

					if ( is_array( $_REQUEST['product_id'] ) ) {
						for ( $i = 0; $i < count( $_REQUEST['product_id'] ); $i ++ ) {
							WC()->cart->add_to_cart( $_REQUEST['product_id'][ $i ], $_REQUEST['quantity'][ $i ], $_REQUEST['variation_id'][ $i ], $_REQUEST['variation'][ $i ], $_REQUEST['cart_item_data'][ $i ] );
						}
					} else {
						WC()->cart->add_to_cart( $_REQUEST['product_id'], $_REQUEST['quantity'], $_REQUEST['variation_id'], $_REQUEST['variation'], $_REQUEST['cart_item_data'] );
					}

					$order->set_shipping_total( WC()->cart->get_shipping_total() );
					$order->set_discount_total( WC()->cart->get_discount_total() );
					$order->set_discount_tax( WC()->cart->get_discount_tax() );
					$order->set_cart_tax( WC()->cart->get_cart_contents_tax() + WC()->cart->get_fee_tax() );
					$order->set_shipping_tax( WC()->cart->get_shipping_tax() );
					$order->set_total( WC()->cart->get_total( 'edit' ) );
					WC()->checkout()->create_order_line_items( $order, WC()->cart );
					WC()->checkout()->create_order_fee_lines( $order, WC()->cart );
					WC()->checkout()->create_order_shipping_lines( $order, WC()->session->get( 'chosen_shipping_methods' ), WC()->shipping->get_packages() );
					WC()->checkout()->create_order_tax_lines( $order, WC()->cart );
					WC()->checkout()->create_order_coupon_lines( $order, WC()->cart );

					$order->save();
				}
			} catch ( Exception $e ) {
				throw $e;
			}

			return $order;
		}
		public static function update_order( $data, $order ) {
			foreach ( $data as $key => $value ) {
				if ( is_callable( array ( $order, "set_{$key}" ) ) ) {
					$order->{"set_{$key}"}( $value );
				} elseif ( ( 0 === stripos( $key, 'billing_' ) || 0 === stripos( $key, 'shipping_' ) )
				           && ! in_array( $key, array ( 'shipping_method', 'shipping_total', 'shipping_tax' ) ) ) {
					$order->update_meta_data( '_' . $key, $value );
				}
			}

			$order_id = $order->save();

			do_action( 'woocommerce_checkout_update_order_meta', $order_id, $data );

			return $order_id;
		}
		public static function get_order_for_simple_payment( $uid = null ) {
			wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );
			$params = WC()->checkout()->get_posted_data();
			if ( is_user_logged_in() ) {
				self::process_customer( $params );
			}
			$order = PAFW_Simple_Pay::get_order_awaiting_payment( $uid );

			$order = self::create_order( $params, $order );

			do_action( 'woocommerce_checkout_order_processed', $order->get_id(), $params, $order );

			if ( empty( $order->get_billing_first_name() ) ) {
				throw new Exception( __( '고객정보를 입력해주세요. 구매자 이름은 필수입니다.', 'pgall-for-woocommerce' ) );
			}

			if ( empty( $order->get_billing_email() ) ) {
				throw new Exception( __( '고객정보를 입력해주세요. 구매자 이메일은 필수입니다.', 'pgall-for-woocommerce' ) );
			}

			if ( empty( $order->get_billing_phone() ) ) {
				throw new Exception( __( '고객정보를 입력해주세요. 구매자 전화번호는 필수입니다.', 'pgall-for-woocommerce' ) );
			}

			if ( isset( $_REQUEST['mshop_billing_address-postnum'] ) && isset( $_REQUEST['mshop_billing_address-addr1'] ) ) {
				if ( empty( $_REQUEST['mshop_billing_address-postnum'] ) || empty( $_REQUEST['mshop_billing_address-addr1'] ) ) {
					throw new Exception( __( '주소를 입력해주세요.', 'pgall-for-woocommerce' ) );
				}
			} else if ( isset( $_REQUEST['billing_postcode'] ) && isset( $_REQUEST['billing_address_1'] ) ) {
				if ( empty( $_REQUEST['billing_postcode'] ) || empty( $_REQUEST['billing_address_1'] ) ) {
					throw new Exception( __( '주소를 입력해주세요.', 'pgall-for-woocommerce' ) );
				}
			}

			return $order;
		}
		public static function get_order_awaiting_payment( $uid ) {
			$orders = wc_get_orders( array (
				'status'    => array ( 'wc-pending', 'wc-failed' ),
				'order_key' => $uid
			) );

			if ( ! empty( $orders ) ) {
				return current( $orders );
			}

			return false;
		}

		public static function create_simple_pay_order( $data ) {
			try {
				$order = new WC_Order();

				foreach ( $data as $key => $value ) {
					if ( is_callable( array ( $order, "set_{$key}" ) ) ) {
						$order->{"set_{$key}"}( $value );
					} elseif ( ( 0 === stripos( $key, 'billing_' ) || 0 === stripos( $key, 'shipping_' ) )
					           && ! in_array( $key, array ( 'shipping_method', 'shipping_total', 'shipping_tax' ) ) ) {
						$order->update_meta_data( '_' . $key, $value );
					}
				}

				$order->set_created_via( 'checkout' );
				$order->set_customer_id( apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() ) );
				$order->set_currency( get_woocommerce_currency() );
				$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
				$order->set_customer_ip_address( WC_Geolocation::get_ip_address() );
				$order->set_customer_user_agent( wc_get_user_agent() );
				$order->set_customer_note( isset( $data['order_comments'] ) ? $data['order_comments'] : '' );
				do_action( 'woocommerce_checkout_create_order', $order, $data );

				// Save the order.
				$order_id = $order->save();

				$order = wc_get_order( $order );

				do_action( 'woocommerce_checkout_update_order_meta', $order_id, $data );

				pafw_update_meta_data( $order, '_simple_pay', 'yes' );
				pafw_update_meta_data( $order, '_simple_pay_redirect_url', $_SERVER['HTTP_REFERER'] );
				if ( isset( $_REQUEST['order_received_url'] ) ) {
					pafw_update_meta_data( $order, '_simple_pay_order_received_url', $_REQUEST['order_received_url'] );
				}

				$order->set_order_key( $_REQUEST['_pafw_uid'] );
				$order_id = $order->save();

				return $order_id;
			} catch ( Exception $e ) {
				return new WP_Error( 'checkout-error', $e->getMessage() );
			}
		}

		public static function redirect_url( $redirect_url, $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order && 'yes' == pafw_get_meta( $order, '_simple_pay', 'no' ) ) {
				if ( in_array( $order->get_status(), array ( 'pending', 'cancelled', 'failed' ) ) ) {
					$redirect_url = pafw_get_meta( $order, '_simple_pay_redirect_url' );
				} else {
					$order_received_url = pafw_get_meta( $order, '_simple_pay_order_received_url' );

					if ( ! empty( $order_received_url ) ) {
						$redirect_url = $order_received_url;
					}
				}
			}

			return $redirect_url;
		}
	}

	PAFW_Simple_Pay::init();

endif;