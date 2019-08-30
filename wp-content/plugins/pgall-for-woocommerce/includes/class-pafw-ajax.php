<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class PAFW_Ajax {
	static $slug;
	public static function init() {
		self::$slug = PAFW()->slug();
		self::add_ajax_events();
	}
	public static function add_ajax_events() {

		$ajax_events = array (
			'pafw_ajax_action'        => true,
			'pafw_simple_payment'     => true,
			'request_exchange_return' => true,
			'launch_payment'          => true
		);

		if ( is_admin() ) {
			$ajax_events = array_merge( $ajax_events, array (
				'update_pafw_settings'                        => false,
				'update_pafw_review_settings'                 => false,
				'update_pafw_payment_method_control_settings' => false,
				'update_inicis_settings'                      => false,
				'update_nicepay_settings'                     => false,
				'update_kcp_settings'                         => false,
				'update_lguplus_settings'                     => false,
				'update_payco_settings'                       => false,
				'update_kakaopay_settings'                    => false,
				'pafw_sales_action'                           => false,
				'pafw_payment_statistics_action'              => false,
				'agree_to_tac'                                => false,
				'migration_subscription'                      => false,
				'inicis_register_gateway'                     => false,
				'target_search'                               => false
			) );
		}

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_' . self::$slug . '-' . $ajax_event, array ( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_' . self::$slug . '-' . $ajax_event, array ( __CLASS__, $ajax_event ) );
			}
		}
		add_action( 'wp_ajax_woocommerce_delete_refund', array ( __CLASS__, 'delete_exchange_return' ), 1 );
	}

	public static function update_pafw_settings() {
		PAFW_Admin_Settings::update_settings();
	}

	public static function update_pafw_review_settings() {
		PAFW_Admin_Review_Settings::update_settings();
	}

	public static function update_pafw_payment_method_control_settings() {
		PAFW_Admin_Payment_Method_Control_Settings::update_settings();
	}

	public static function update_inicis_settings() {
		WC_Gateway_PAFW_Inicis::update_settings();
	}

	public static function update_nicepay_settings() {
		WC_Gateway_PAFW_Nicepay::update_settings();
	}

	public static function update_kcp_settings() {
		WC_Gateway_PAFW_Kcp::update_settings();
	}

	public static function update_lguplus_settings() {
		WC_Gateway_PAFW_LGUPlus::update_settings();
	}

	public static function update_payco_settings() {
		WC_Gateway_PAFW_Payco::update_settings();
	}

	public static function update_kakaopay_settings() {
		WC_Gateway_PAFW_KakaoPay::update_settings();
	}
	public static function pafw_ajax_action() {

		try {
			if ( isset( $_REQUEST['payment_method'] ) && isset( $_REQUEST['payment_action'] ) ) {
				$payment_method = $_REQUEST['payment_method'];
				$payment_action = $_REQUEST['payment_action'];

				$payment_gateway = pafw_get_payment_gateway( $payment_method );

				if ( $payment_gateway && is_callable( array ( $payment_gateway, $payment_action ) ) ) {
					$payment_gateway->$payment_action();
				}
			}

			wp_send_json_error( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ) );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}

	}
	public static function pafw_sales_action() {
		try {
			if ( isset( $_REQUEST['command'] ) ) {
				$command = $_REQUEST['command'];

				if ( is_callable( array ( 'PAFW_Admin_Sales', $command ) ) ) {
					PAFW_Admin_Sales::$command();
				}
			}

			wp_send_json_error( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ) );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}

	}
	public static function pafw_payment_statistics_action() {
		try {
			if ( isset( $_REQUEST['command'] ) ) {
				$command = $_REQUEST['command'];

				if ( is_callable( array ( 'PAFW_Admin_Payment_Statistics', $command ) ) ) {
					PAFW_Admin_Payment_Statistics::$command();
				}
			}

			wp_send_json_error( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ) );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}

	}
	public static function request_exchange_return() {
		try {
			check_ajax_referer( 'request_exchange_return' );

			$exchange_return_order = PAFW_Exchange_Return_Manager::create_exchange_return( $_REQUEST );

			if ( is_wp_error( $exchange_return_order ) ) {
				throw new Exception( $exchange_return_order->get_error_messages() );
			}
			WC()->mailer();
			do_action( 'pafw-' . $_REQUEST['type'] . '-request-notification', $exchange_return_order->get_id(), $exchange_return_order );

			$message = sprintf( __( '%s 요청이 접수되었습니다.', 'pgall-for-woocommerce' ), 'exchange' == $_REQUEST['type'] ? '교환' : '반품' );

			$parent_order = wc_get_order( $_REQUEST['order_id'] );
			$parent_order->update_status( $_REQUEST['type'] . '-request', $message );

			$redirect_url = pafw_get( $_REQUEST, 'redirect_url', wc_get_account_endpoint_url( 'orders' ) );

			wp_send_json_success( array ( 'message' => $message, 'redirect_url' => $redirect_url ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array ( 'message' => $e->getMessage() ) );
		}
	}

	public static function delete_exchange_return() {
		check_ajax_referer( 'order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			die( - 1 );
		}

		$exchange_return_ids = array_map( 'absint', is_array( $_POST['refund_id'] ) ? $_POST['refund_id'] : array ( $_POST['refund_id'] ) );
		foreach ( $exchange_return_ids as $exchange_return_id ) {
			if ( $exchange_return_id && 'shop_order_pafw_ex' === get_post_type( $exchange_return_id ) ) {
				$order_id = wp_get_post_parent_id( $exchange_return_id );
				wc_delete_shop_order_transients( $order_id );
				wp_delete_post( $exchange_return_id );
				do_action( 'pafw_exchange_return_deleted', $exchange_return_id, $order_id );
			}
		}
	}

	public static function agree_to_tac() {
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			die( - 1 );
		}

		update_option( PAFW()->slug() . '-agree-to-tac', 'yes', false );

		PAFW_Admin_Notice::update_usage_statictic();

		wp_send_json_success( array ( 'reload' => true ) );
	}

	public static function migration_subscription() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die( - 1 );
		}

		PAFW_Admin_Tools::do_migrate();

		wp_send_json_success( array ( 'reload' => true ) );
	}

	public static function inicis_register_gateway() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die( - 1 );
		}

		try {
			PAFW_Gateway::inicis_register_gateway();

			wp_send_json_success( array ( 'message' => __( '게이트웨이에 등록되었슶니다.', 'pgall-for-woocommerce' ) ) );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}
	static function make_taxonomy_tree( $taxonomy, $args, $depth = 0, $parent = 0, $paths = array () ) {
		$results = array ();

		$args['parent'] = $parent;
		$terms          = get_terms( $taxonomy, $args );

		foreach ( $terms as $term ) {
			$current_paths = array_merge( $paths, array ( $term->name ) );
			$results[]     = array (
				"name"  => '<span class="tree-indicator-desc">' . implode( '-', $current_paths ) . '</span><span class="tree-indicator" style="margin-left: ' . ( $depth * 8 ) . 'px;">' . $term->name . '</span>',
				"value" => $term->term_id
			);

			$results = array_merge( $results, self::make_taxonomy_tree( $taxonomy, $args, $depth + 1, $term->term_id, $current_paths ) );
		}

		return $results;
	}
	static function target_search_category( $depth = 0, $parent = 0 ) {
		$args = array ();

		if ( ! empty( $_REQUEST['args'] ) ) {
			$args['name__like'] = $_REQUEST['args'];
		}

		$results = self::make_taxonomy_tree( 'product_cat', $args );

		$respose = array (
			'success' => true,
			'results' => $results
		);

		echo json_encode( $respose );
		die();
	}
	static function target_search_attributes() {
		$results = array ();

		foreach ( wc_get_attribute_taxonomies() as $attribute_taxonomy ) {
			$terms = get_terms( wc_attribute_taxonomy_name( $attribute_taxonomy->attribute_name ) );
			foreach ( $terms as $term ) {
				$label     = $attribute_taxonomy->attribute_label . ' - ' . $term->name;
				$results[] = array (
					"name"  => '<span class="tree-indicator-desc">' . $label . '</span><span class="tree-indicator">' . $label . '</span>',
					"value" => $term->term_id
				);
			}
		}

		$response = array (
			'success' => true,
			'results' => $results
		);

		echo json_encode( $response );
		die();
	}
	static function target_search_product_posts_title_like( $where, &$wp_query ) {
		global $wpdb;
		if ( $posts_title = $wp_query->get( 'posts_title' ) ) {
			$where .= ' AND ' . $wpdb->posts . '.post_title LIKE "%' . $posts_title . '%"';
		}

		return $where;
	}
	static function target_search_product() {
		$keyword = ! empty( $_REQUEST['args'] ) ? $_REQUEST['args'] : '';

		add_filter( 'posts_where', array ( __CLASS__, 'target_search_product_posts_title_like' ), 10, 2 );
		$args = array (
			'post_type'      => 'product',
			'posts_title'    => $keyword,
			'post_status'    => 'publish',
			'posts_per_page' => - 1
		);

		$query = new WP_Query( $args );

		remove_filter( 'posts_where', array ( __CLASS__, 'target_search_product_posts_title_like' ) );

		$results = array ();

		foreach ( $query->posts as $post ) {
			$results[] = array (
				"name"  => $post->post_title,
				"value" => $post->ID
			);
		}
		$respose = array (
			'success' => true,
			'results' => $results
		);

		echo json_encode( $respose );

		die();
	}

	public static function target_search() {
		if ( ! empty( $_REQUEST['type'] ) ) {
			$type = $_REQUEST['type'];

			switch ( $type ) {
				case 'product' :
				case 'product-category' :
					self::target_search_product();
					break;
				case 'category' :
					self::target_search_category();
					break;
				case 'attributes' :
					self::target_search_attributes();
					break;
				default:
					die();
					break;
			}
		}
	}

	public static function launch_payment() {
		PAFW_Shortcodes::launch_payment();
	}
	public static function pafw_simple_payment() {

		try {
			if ( isset( $_REQUEST['payment_method'] ) ) {
				$order = PAFW_Simple_Pay::get_order_for_simple_payment( $_REQUEST['_pafw_uid'] );

				$payment_gateway = pafw_get_payment_gateway( $_REQUEST['payment_method'] );

				if ( $payment_gateway ) {
					$result = $payment_gateway->process_payment( pafw_get_object_property( $order, 'id' ) );

					if ( $result && 'success' == $result['result'] ) {
						die( json_encode( $result ) );
					} else {
						$result = array (
							'result'   => 'fail',
							'messages' => wc_print_notices( true )
						);

						die( json_encode( $result ) );
					}
				}
			}

			throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ) );
		} catch ( Exception $e ) {
			ob_start();

			wc_get_template( "notices/error.php", array (
				'messages' => array ( $e->getMessage() )
			) );

			$notices = wc_kses_notice( ob_get_clean() );

			die( json_encode( array (
				'result'   => 'fail',
				'messages' => $notices
			) ) );
		}
	}
}

//초기화 수행
PAFW_Ajax::init();