<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Cancel_Unpaid_Order' ) ) :

	class PAFW_Cancel_Unpaid_Order {

		public static function init() {
			if ( 'yes' == get_option( 'pafw-gw-support-cancel-unpaid-order', 'no' ) && get_option( 'pafw-gw-cancel-unpaid-order-days', '3' ) > 0 ) {
				add_filter( 'cron_schedules', array ( __CLASS__, 'pafw_cancel_unpaid_order_interval' ) );
				add_action( 'wp', array ( __CLASS__, 'cancel_unpaid_order_cron_init' ) );
				add_action( 'pafw_cancel_unpaid_order_hook', array ( __CLASS__, 'cancel_unpaid_order' ) );
			}
		}

		public static function cancel_unpaid_order_cron_init() {

			if ( ! wp_next_scheduled( 'pafw_cancel_unpaid_order_hook' ) ) {
				wp_schedule_event( time(), 'pafw_cancel_unpaid_order_interval', 'pafw_cancel_unpaid_order_hook' );
			} else {
				$schedule = wp_get_schedule( 'pafw_cancel_unpaid_order_hook' );

				if ( $schedule != 'pafw_cancel_unpaid_order_interval' ) {
					$timestamp = wp_next_scheduled( 'pafw_cancel_unpaid_order_hook' );
					wp_unschedule_event( $timestamp, 'pafw_cancel_unpaid_order_hook', array () );
					wp_schedule_event( time(), 'pafw_cancel_unpaid_order_interval', 'pafw_cancel_unpaid_order_hook' );
				}
			}
		}
		public static function pafw_cancel_unpaid_order_interval( $schedules ) {

			$schedules['pafw_cancel_unpaid_order_interval'] = array (
				'interval' => 1800,
				'display'  => __( '매 30 분 간격', 'mshop-bacs-restore-stock' )
			);

			return $schedules;
		}

		public static function get_supported_gateways() {
			$supported_gateway_ids = array ();
			$available_gateways    = WC()->payment_gateways()->payment_gateways();
			foreach ( $available_gateways as $gateway_id => $gateway ) {
				if ( 'bacs' == $gateway_id || $gateway->supports( 'pafw-vbank' ) ) {
					$supported_gateway_ids[] = $gateway_id;
				}
			}

			return apply_filters( 'pafw_cancel_upaid_order_supported_gateway_ids', $supported_gateway_ids );
		}

		public static function get_unpaid_orders( $days, $payment_gateway_ids ) {
			global $wpdb;

			$date = date( "Y-m-d H:i:s", strtotime( '-' . absint( $days ) . ' day', strtotime( current_time( 'mysql' ) ) ) );

			$unpaid_orders = $wpdb->get_col( $wpdb->prepare( "
				SELECT posts.ID
				FROM {$wpdb->posts} AS posts
				LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
				WHERE   
					posts.post_type   IN ('" . implode( "','", wc_get_order_types() ) . "')
					AND posts.post_status = 'wc-on-hold'
					AND posts.post_modified < %s
                    AND postmeta.meta_key = '_payment_method'
                    AND postmeta.meta_value IN ('" . implode( "','", $payment_gateway_ids ) . "')
			", $date ) );

			return $unpaid_orders;
		}
		static function restore_stock( $order ) {
			if ( 'yes' == get_option( 'woocommerce_manage_stock' ) ) {

				foreach ( $order->get_items() as $item ) {

					$_product = $order->get_product_from_item( $item );

					if ( $_product && $_product->exists() && $_product->managing_stock() ) {

						$qty       = apply_filters( 'woocommerce_order_item_quantity', $item['qty'], $order, $item );
						$old_stock = $_product->get_stock_quantity();
						$new_stock = $_product->increase_stock( $qty );

						$order->add_order_note( sprintf( __( '[무통장입금 자동취소] 상품 <a target="_blank" href="%s">#%s</a>, %s의 재고가 %s 에서 %s 으로 복구되었습니다.', 'pgall-for-woocommerce' ), get_edit_post_link( $_product->get_id() ), $_product->get_id(), $_product->get_name(), $old_stock, $new_stock ) );

						$order->send_stock_notifications( $_product, $new_stock, $item['qty'] );

					}
				}
			}
		}

		public static function cancel_unpaid_order() {
			if ( 'yes' == get_option( 'pafw-gw-support-cancel-unpaid-order', 'no' ) && get_option( 'pafw-gw-cancel-unpaid-order-days', '3' ) > 0 ) {
				$days             = get_option( 'pafw-gw-cancel-unpaid-order-days', '3' );
				$gateway_ids      = self::get_supported_gateways();
				$unpaid_order_ids = self::get_unpaid_orders( $days, $gateway_ids );

				if ( ! empty( $unpaid_order_ids ) ) {
					foreach ( $unpaid_order_ids as $unpaid_order_id ) {
						$order = wc_get_order( $unpaid_order_id );

						if ( is_callable( array ( $order, 'get_created_via' ) ) ) {
							$created_via = $order->get_created_via();
						} else {
							$created_via = pafw_get_meta( $order, '_created_via' );
						}

						if ( $order && 'checkout' === $created_via ) {
							$payment_method = pafw_get_object_property( $order, 'payment_method' );

							if ( 'bacs' == $payment_method) {
								$order->update_status( 'cancelled', __( '[무통장입금 자동취소] 지불되지 않은 무통장입금(Bacs) 주문이 취소 처리 되었습니다.', 'pgall-for-woocommerce' ) );
								self::restore_stock( $order );
							} else {
								$payment_gateway = pafw_get_payment_gateway( $payment_method );
								if ( $payment_gateway instanceof PAFW_Payment_Gateway && is_callable( array ( $payment_gateway, 'cancel_unpaid_order' ) ) ) {
									if( $payment_gateway->cancel_unpaid_order( $order ) ) {
										self::restore_stock( $order );
									}
								}
							}
						}
					}
				}
			}
		}

	}

	PAFW_Cancel_Unpaid_Order::init();

endif;
