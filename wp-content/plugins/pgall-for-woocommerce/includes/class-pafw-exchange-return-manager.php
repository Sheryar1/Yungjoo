<?php

if ( ! class_exists( 'PAFW_Exchange_Return_Manager' ) ) {

	class PAFW_Exchange_Return_Manager {

		private static $request_status = array ( 'exchange-request', 'return-request' );
		private static $accept_status = array ( 'accept-exchange', 'accept-return' );


		private static $valid_order_statuses_for_exchange_return = null;

		private static $cached_orders = array ();
		public static function pafw_order_is_editable( $editable, $order ) {
			return $editable || in_array( $order->get_status(), array ( 'return-request', 'exchange-request', 'accept-exchange', 'accept-return' ) );
		}

		public static function support_exchange_return() {
			return self::support_exchange() || self::support_return();
		}

		public static function support_exchange() {
			return 'yes' == get_option( 'pafw-gw-support-exchange', 'no' );
		}

		public static function support_return() {
			return 'yes' == get_option( 'pafw-gw-support-return', 'no' );
		}

		public static function get_label() {
			if ( self::support_exchange() && self::support_return() ) {
				return __( '교환 / 반품', 'pgall-for-woocommerce' );
			} else if ( self::support_exchange() ) {
				return __( '교환', 'pgall-for-woocommerce' );
			} else {
				return __( '반품', 'pgall-for-woocommerce' );
			}
		}
		public static function add_exchange_return_actions( $actions, $order ) {
			if ( self::support_exchange_return() && PAFW_Exchange_Return_Manager::can_exchange_return( $order ) ) {
				$actions['exchange_return_request'] = array (
					'url'  => wc_get_endpoint_url( 'pafw-ex', pafw_get_object_property( $order, 'id' ) ),
					'name' => self::get_label()
				);
			}

			return $actions;
		}
		public static function get_valid_exchange_return_order_items( $order ) {
			// Make order items info
			$order_items = array ();
			foreach ( $order->get_items() as $key => $item ) {
				$order_items[ $key ] = intval( $item['qty'] );
			}

			$exchange_return_orders = self::get_exchange_return_orders( $order );
			if ( empty( $exchange_return_orders ) ) {
				return $order_items;
			}

			// Remove exchanged or returned items from order items info
			foreach ( $exchange_return_orders as $exchage_return_order ) {
				foreach ( $exchage_return_order->get_items() as $item ) {
					$order_items[ $item['exchange_return_item_id'] ] -= $item['qty'];
				}
			}

			// Filter valid order items
			$order_items = array_filter( $order_items, function ( $item ) {
				return $item > 0;
			} );

			return $order_items;
		}
		public static function can_exchange_return( $order ) {
			$order_statuses = self::get_valid_order_statuses_for_exchange_or_return();

			if ( ! in_array( $order->get_status(), $order_statuses ) ) {
				return false;
			}


			$order_items = self::get_valid_exchange_return_order_items( $order );
			if ( empty( $order_items ) ) {
				return false;
			}
			if ( empty( pafw_get_meta( $order, '_pafw_ex_date' ) ) ) {
				return false;
			}

			$today    = date_create( current_time( 'mysql' ) );
			$ex_date  = date_create( pafw_get_meta( $order, '_pafw_ex_date' ) );
			$interval = date_diff( $today, $ex_date )->format( '%a' );

			if ( $interval > get_option( 'pafw-gw-ex-terms', '3' ) ) {
				return false;
			}

			return true;
		}
		public static function get_valid_order_statuses_for_exchange_or_return() {
			return apply_filters( 'pafw_get_valid_order_statuses_for_exchange_or_return', array ( 'shipped', 'completed' ) );
		}
		public static function get_exchange_return_orders( $order ) {
			$order_id = pafw_get_object_property( $order, 'id' );
			$orders   = pafw_get( self::$cached_orders, $order_id, null );

			if ( is_null( $orders ) ) {
				self::$cached_orders[ $order_id ] = wc_get_orders( array (
					'type'   => 'shop_order_pafw_ex',
					'parent' => $order_id,
					'limit'  => - 1,
				) );

				$orders = pafw_get( self::$cached_orders, $order_id, null );
			}

			return $orders;
		}
		public static function create_exchange_return( $args = array () ) {
			$default_args = array (
				'reason'     => null,
				'order_id'   => 0,
				'refund_id'  => 0,
				'line_items' => array (),
				'date'       => current_time( 'mysql', 0 )
			);

			$args = wp_parse_args( $args, $default_args );

			if ( empty( $args['order_items'] ) ) {
				throw new Exception( __( '교환 또는 반품할 상품을 선택해주세요.' ) );
			}

			if ( empty( $args['reason'] ) ) {
				throw new Exception( __( '교환 또는 반품 사유를 입력해주세요.' ) );
			}

			$exchange_return_data = array ();

			if ( ! empty( $args['exchange_return_id'] ) && $args['exchange_return_id'] > 0 ) {
				$updating                   = true;
				$exchange_return_data['ID'] = $args['exchange_return_id'];
			} else {
				$updating                              = false;
				$exchange_return_data['post_type']     = 'shop_order_pafw_ex';
				$exchange_return_data['post_status']   = 'wc-processing';
				$exchange_return_data['ping_status']   = 'closed';
				$exchange_return_data['post_author']   = get_current_user_id() ? get_current_user_id() : 1;
				$exchange_return_data['post_password'] = uniqid( 'refund_' );
				$exchange_return_data['post_parent']   = absint( $args['order_id'] );
				$exchange_return_data['post_title']    = sprintf( __( 'Exchange or Return &ndash; %s', 'pgall-for-woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'pgall-for-woocommerce' ) ) );
				$exchange_return_data['post_date']     = $args['date'];
			}

			if ( ! is_null( $args['reason'] ) ) {
				$exchange_return_data['post_excerpt'] = $args['reason'];
			}

			if ( $updating ) {
				$exchange_return_id = wp_update_post( $exchange_return_data );
			} else {
				$exchange_return_id = wp_insert_post( apply_filters( 'pafw_new_exchange_return_data', $exchange_return_data ), true );
			}

			if ( is_wp_error( $exchange_return_id ) ) {
				return $exchange_return_id;
			}

			if ( ! $updating ) {
				// Get exchange or return object
				$exchange_return = wc_get_order( $exchange_return_id );
				$order           = wc_get_order( $args['order_id'] );

				// Default exchange or return meta data
				pafw_update_meta_data( $exchange_return, '_type', $args['type'] );
				pafw_update_meta_data( $exchange_return, '_reason', $args['reason'] );
				pafw_update_meta_data( $exchange_return, '_requests', pafw_get( $args, 'requests' ) );

				if ( sizeof( $args['order_items'] ) > 0 ) {
					$order_items = $order->get_items();

					foreach ( array_keys( $args['order_items'] ) as $exchange_return_item_id ) {
						if ( isset( $order_items[ $exchange_return_item_id ] ) ) {
							if ( empty( $args['exchange_return_qty'][ $exchange_return_item_id ] ) ) {
								continue;
							}

							$new_item_id = $exchange_return->add_product( $order->get_product_from_item( $order_items[ $exchange_return_item_id ] ), $args['exchange_return_qty'][ $exchange_return_item_id ] );
							wc_add_order_item_meta( $new_item_id, '_exchange_return_item_id', $exchange_return_item_id );

							if ( ! empty( $order_items[ $exchange_return_item_id ]['variation_id'] ) ) {
								$variations = wc_get_product_variation_attributes( $order_items[ $exchange_return_item_id ]['variation_id'] );
								foreach ( $variations as $key => $value ) {
									wc_add_order_item_meta( $new_item_id, str_replace( 'attribute_', '', $key ), $value );
								}
							}
						}
					}
				}

				$exchange_return->calculate_totals();
				if( ! empty( $args['refund_bank_account'] ) ) {
					$order->add_order_note( sprintf('[환불계좌정보]<br>교환/반품 번호 : #%d<br>%s',$exchange_return_id, $args['refund_bank_account'] ) );
				}

				do_action( 'pafw_exchange_return_created', $exchange_return_id, $args );
			}

			// Clear transients
			wc_delete_shop_order_transients( $args['order_id'] );

			return new PAFW_Order_Exchange_Return( $exchange_return_id );
		}
		public static function woocommerce_order_status_changed( $order_id, $old_status, $new_status ) {
			if ( self::support_exchange_return() ) {
				if ( in_array( $old_status, self::$accept_status ) || ( in_array( $old_status, self::$request_status ) && ! in_array( $new_status, self::$accept_status ) ) ) {
					$ex_orders = self::get_exchange_return_orders( $order_id );

					if ( ! empty( $ex_orders ) ) {
						foreach ( $ex_orders as $ex_order ) {
							if ( 'processing' == $ex_order->get_status() ) {
								$ex_order->update_status( 'completed' );
							}
						}
					}
				}
				if ( in_array( $new_status, self::get_valid_order_statuses_for_exchange_or_return() ) ) {
					$order = wc_get_order( $order_id );
					pafw_update_meta_data( $order, '_pafw_ex_date', current_time( 'mysql' ) );
				}
			}
		}
	}

}