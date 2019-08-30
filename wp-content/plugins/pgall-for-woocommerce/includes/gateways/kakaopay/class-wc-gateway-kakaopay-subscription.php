<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_KakaoPay_Subscription' ) ) {

		class WC_Gateway_KakaoPay_Subscription extends WC_Gateway_KakaoPay {

			public function __construct() {

				$this->id = 'kakaopay_subscription';

				parent::__construct();

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '카카오페이 정기결제', 'pgall-for-woocommerce' );
					$this->description = __( '카카오페이 정기결제로 결제합니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}

				$this->countries = array ( 'KR' );
				$this->supports  = array (
					'subscriptions',
					'subscription_cancellation',
					'subscription_suspension',
					'subscription_reactivation',
					'subscription_amount_changes',
					'subscription_date_changes',
					'subscription_payment_method_change_customer',
					'subscription_payment_method_change_admin',
					'pafw',
					'pafw_additional_charge'
				);
				if ( 'yes' == $this->settings['support_multiple_subscriptions'] ) {
					$this->supports[] = 'multiple_subscriptions';
				}
				if ( 'yes' == $this->settings['support_products'] ) {
					$this->supports[] = 'products';
				}

				add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array ( $this, 'woocommerce_scheduled_subscription_payment' ), 10, 2 );
				add_action( 'woocommerce_subscription_status_cancelled', array ( $this, 'cancel_subscription' ) );
				add_action( 'woocommerce_subscription_cancelled_' . $this->id, array ( $this, 'cancel_subscription' ) );

				add_action( 'woocommerce_subscriptions_pre_update_payment_method', array ( $this, 'maybe_remove_subscription_cancelled_callback' ), 10, 3 );
				add_action( 'woocommerce_subscription_payment_method_updated', array ( $this, 'maybe_reattach_subscription_cancelled_callback' ), 10, 3 );
			}

			function adjust_settings() {
				$this->settings['cid']            = $this->settings['cid_subscription'];
				$this->settings['operation_mode'] = $this->settings['operation_mode_subscription'];
				$this->settings['test_user_id']   = $this->settings['test_user_id_subscription'];
			}

			function is_available() {

				if ( parent::is_available() && is_checkout() ) {
					if ( ! in_array( 'products', $this->supports ) && class_exists( 'WC_Subscriptions_Cart' ) && ! WC_Subscriptions_Cart::cart_contains_subscription() && ! isset( $_GET['change_payment_method'] ) && ( ! isset( $_GET['order_id'] ) || ! wcs_order_contains_subscription( $_GET['order_id'] ) ) ) {
						return false;
					}
				}

				return parent::is_available();
			}

			public function subscription_additional_charge() {
				check_ajax_referer( 'pgall-for-woocommerce' );

				if ( ! current_user_can( 'publish_shop_orders' ) ) {
					throw new Exception( __( '주문 관리 권한이 없습니다.', 'pgall-for-woocommerce' ) );
				}

				$order = wc_get_order( $_REQUEST['order_id'] );

				if ( $order ) {
					$amount   = $_REQUEST['amount'];
					$order_id = pafw_get_object_property( $order, 'id' ) . '_' . date( 'YmdHis' );

					$params = array (
						'cid'              => $this->cid,
						'sid'              => pafw_get_meta( $order, '_pafw_subscription_batch_key' ),
						'partner_order_id' => $order_id,
						'partner_user_id'  => $order->get_user_id(),
						'item_name'        => $this->make_product_info( $order ),
						'quantity'         => $order->get_item_count(),
						'total_amount'     => $amount,
						'tax_free_amount'  => 0,
						'vat_amount'       => $this->calculate_tax( $amount )
					);

					$response = $this->call_api( 'subscription', $params );

					if ( empty( $response ) ) {
						throw new PAFW_Exception( __( '추가 과금 요청중 오류가 발생했습니다.', 'pgall-for-woocommerce' ), '9001' );
					} else if ( is_wp_error( $response ) ) {
						throw new PAFW_Exception( __( '추가 과금 요청중 오류가 발생했습니다.', 'pgall-for-woocommerce' ) . $response->get_error_message(), '9001' );
					}

					if ( ! empty( $response['aid'] ) ) {

						do_action( 'pafw_payment_action', 'completed', $_REQUEST['amount'], $order, $this );
						$history = pafw_get_meta( $order, '_pafw_additional_charge_history' );
						if ( empty( $history ) ) {
							$history = array ();
						}

						$history[ $response['tid'] ] = array (
							'status'         => 'PAYED',
							'auth_date'      => $response['approved_at'],
							'charged_amount' => $response['amount']['total']
						);

						pafw_update_meta_data( $order, '_pafw_additional_charge_history', $history );

						if ( $this->supports( 'subscriptions' ) ) {
							$this->add_payment_log( $order, '[ 추가 과금 성공 ]', array (
								'거래요청번호' => $response['aid'],
								'주문번호'   => $order_id,
								'추가과금금액' => wc_price( $amount )
							) );
						}

						wp_send_json_success( '추가 과금 요청이 정상적으로 처리되었습니다.' );
					} else {
						$this->add_payment_log( $order, '[ 추가 과금 오류 ] ', json_encode( $response ), false );
						throw new PAFW_Exception( "[ 추가 과금 오류 ]\n" . json_encode( $response ), '2004', $response['code'] );
					}

				}
			}

			public function subscription_cancel_additional_charge() {
				check_ajax_referer( 'pgall-for-woocommerce' );

				if ( ! current_user_can( 'publish_shop_orders' ) ) {
					throw new Exception( __( '주문 관리 권한이 없습니다.', 'pgall-for-woocommerce' ) );
				}

				$order  = wc_get_order( $_REQUEST['order_id'] );
				$params = array (
					'cid'                    => $this->cid,
					'tid'                    => $_REQUEST['tid'],
					'cancel_amount'          => $_REQUEST['amount'],
					'cancel_tax_free_amount' => 0,
					'cancel_vat_amount'      => $this->calculate_tax( $_REQUEST['amount'] ),
				);

				$response = $this->call_api( 'cancel', $params );

				if ( $response && ! empty( $response['aid'] ) ) {

					do_action( 'pafw_payment_action', 'cancelled', $_REQUEST['amount'], $order, $this );

					$this->add_payment_log( $order, '[ 추가 과금 취소 성공 ]', array (
						'거래요청번호' => $response['aid'],
						'취소금액'   => wc_price( $_REQUEST['amount'] )
					) );

					$history = pafw_get_meta( $order, '_pafw_additional_charge_history' );

					$history[ $_REQUEST['tid'] ]['status'] = 'CANCELED';

					pafw_update_meta_data( $order, '_pafw_additional_charge_history', $history );

					wp_send_json_success( '추가 과금 취소 요청이 정상적으로 처리되었습니다.' );
				} else {
					throw new Exception( $response['message'], $response['code'] );
				}
			}

			function woocommerce_scheduled_subscription_payment( $amount_to_charge, $order ) {
				try {
					$subscriptions = wcs_get_subscriptions_for_renewal_order( pafw_get_object_property( $order, 'id' ) );

					if ( $order && $subscriptions ) {
						$subscription = current( $subscriptions );

						$params = array (
							'cid'              => $this->cid,
							'sid'              => pafw_get_meta( $subscription, '_pafw_subscription_batch_key' ),
							'partner_order_id' => pafw_get_object_property( $order, 'id' ),
							'partner_user_id'  => $order->get_user_id(),
							'item_name'        => $this->make_product_info( $order ),
							'quantity'         => $order->get_item_count(),
							'total_amount'     => $amount_to_charge,
							'tax_free_amount'  => 0,
							'vat_amount'       => $this->calculate_tax( $amount_to_charge )
						);

						$response = $this->call_api( 'subscription', $params );

						if ( empty( $response ) ) {
							throw new PAFW_Exception( __( '정기결제 요청중 오류가 발생했습니다.', 'pgall-for-woocommerce' ), '9001' );
						} else if ( is_wp_error( $response ) ) {
							throw new PAFW_Exception( __( '정기결제 요청중 오류가 발생했습니다.', 'pgall-for-woocommerce' ) . $response->get_error_message(), '9001' );
						}

						if ( ! empty( $response['aid'] ) ) {
							pafw_update_meta_data( $order, "_pafw_payment_method", $response['payment_method_type'] );
							pafw_update_meta_data( $order, '_pafw_aid', $response['aid'] );
							pafw_update_meta_data( $order, "_pafw_txnid", $response['tid'] );
							pafw_update_meta_data( $order, "_pafw_payed_date", $response['approved_at'] );
							pafw_update_meta_data( $order, "_pafw_total_price", $response['amount']['total'] );
							pafw_update_meta_data( $order, '_pafw_subscription_batch_key', $response['sid'] );

							if ( is_callable( array ( $order, 'set_payment_method_title' ) ) ) {
								$order->set_payment_method_title( $this->title . ' - ' . $response['payment_method_type'] );
							} else {
								pafw_update_meta_data( $order, '_payment_method_title', $this->title . ' - ' . $response['payment_method_type'] );
							}

							if ( 'CARD' == $response['payment_method_type'] ) {
								$card_info = $response['card_info'];
								pafw_update_meta_data( $order, "_pafw_card_code", $card_info['issuer_corp_code'] );
								pafw_update_meta_data( $order, "_pafw_card_bank_code", $card_info['purchase_corp_code'] );
								pafw_update_meta_data( $order, "_pafw_card_name", $card_info['issuer_corp'] );
								pafw_update_meta_data( $order, "_pafw_card_bank_name", $card_info['purchase_corp'] );
								pafw_update_meta_data( $order, "_pafw_card_num", $card_info['bin'] . '**********' );
								pafw_update_meta_data( $order, "_pafw_card_type", $card_info['card_type'] );
								pafw_update_meta_data( $order, "_pafw_install_month", $card_info['install_month'] );
								pafw_update_meta_data( $order, "_pafw_approved_id", $card_info['approved_id'] );
							}

							$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
								'거래요청번호'   => $response['aid'],
								'정기결제 배치키' => $response['sid']
							) );

							$this->payment_complete( $order, $response['tid'] );
						} else {
							throw new PAFW_Exception( json_encode( $response ), '2004', $response['code'] );
						}
					}
				} catch ( Exception $e ) {
					$error_code = '';
					if ( $e instanceof PAFW_Exception ) {
						$error_code = $e->getErrorCode();
					}

					$message = sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() );

					if ( $order ) {
						$order->update_status( 'failed', $message );
					}

					do_action( 'pafw_payment_fail', $order, ! empty( $error_code ) ? $error_code : $e->getCode(), $e->getMessage() );
				}
			}
			function cancel_batch_key( $subscription ) {
				$sid = pafw_get_meta( $subscription, '_pafw_subscription_batch_key' );

				if ( ! empty( $sid ) ) {
					$params = array (
						'cid' => $this->cid,
						'sid' => pafw_get_meta( $subscription, '_pafw_subscription_batch_key' )
					);

					$response = $this->call_api( 'inactive', $params );

					if ( empty( $response ) ) {
						$this->add_payment_log( $subscription, '[ 정기결제 배치키 비활성화 오류 ]' );
					} else if ( is_wp_error( $response ) ) {
						$this->add_payment_log( $subscription, '[ 정기결제 배치키 비활성화 오류 ]', $response->get_error_message() );
					} else {
						if ( $response['sid'] == $sid ) {
							$this->add_payment_log( $subscription, '[ 정기결제 배치키 비활성화 성공 ]', array (
								'정기결제 배치키' => $response['sid'],
								'정기결제 상태'  => $response['status']
							) );
						} else {
							$this->add_payment_log( $subscription, '[ 정기결제 배치키 비활성화 오류 ]', array (
								'CODE' => $response['code'],
								'MSG'  => $response['msg']
							) );
						}
					}
				}
			}
			function cancel_subscription( $subscription ) {
				if ( $subscription && $subscription->get_payment_method() == $this->id ) {
					$this->cancel_batch_key( $subscription );
				}
			}
			public function maybe_remove_subscription_cancelled_callback( $subscription, $new_payment_method, $old_payment_method ) {
				if ( $this->id == $new_payment_method && $this->id == $old_payment_method ) {
					$subscription->add_order_note( __( 'Detach Cancelled Callback', 'pgall-for-woocommerce' ) );
					remove_action( 'woocommerce_subscription_cancelled_' . $this->id, array ( $this, 'cancel_subscription' ) );
				}
			}
			public function maybe_reattach_subscription_cancelled_callback( $subscription, $new_payment_method, $old_payment_method ) {
				if ( $this->id == $new_payment_method && $this->id == $old_payment_method ) {
					$subscription->add_order_note( __( 'Reattach Cancelled Callback', 'pgall-for-woocommerce' ) );
					add_action( 'woocommerce_subscription_cancelled_' . $this->id, array ( $this, 'cancel_subscription' ) );
				}
			}
			public function subscription_cancel_batch_key() {
				if ( ! current_user_can( 'publish_shop_orders' ) ) {
					throw new Exception( __( '[ERR-0000003] 잘못된 요청입니다.', 'pgall-for-woocommerce' ) );
				}

				if ( empty( $_POST['subscription_id'] ) || empty( $_REQUEST['batch_key'] ) ) {
					throw new Exception( __( '[ERR-0000001] 잘못된 요청입니다.', 'pgall-for-woocommerce' ) );
				}

				$subscription = wcs_get_subscription( $_POST['subscription_id'] );
				if ( empty( $subscription ) || $this->id !== $subscription->get_payment_method() ) {
					throw new Exception( __( '[ERR-0000002] 잘못된 요청입니다.', 'pgall-for-woocommerce' ) );
				}

				$params = array (
					'cid' => $this->cid,
					'sid' => $_REQUEST['batch_key']
				);

				$response = $this->call_api( 'inactive', $params );

				if ( empty( $response ) ) {
					throw new Exception( '[ERR-10000001] 정기결제 배치키 비활성화 오류' );
				} else if ( is_wp_error( $response ) ) {
					throw new Exception( '[ERR-10000002] 정기결제 배치키 비활성화 오류 : ' . $response->get_error_message() );
				} else {
					if ( $response['sid'] == $_REQUEST['batch_key'] ) {
						$this->add_payment_log( $subscription, '[ 정기결제 배치키 비활성화 성공 ]', array (
							'정기결제 배치키' => $response['sid'],
							'정기결제 상태'  => $response['status']
						) );
						wp_send_json_success( __( '정기결제 배치키 비활성화가 정상적으로 처리되었습니다.' ) );
					} else {
						throw new Exception( sprintf( '[ERR-10000002] 정기결제 배치키 비활성화 오류 : %s, %s', $response['code'], $response['msg'] ) );
					}
				}
			}
		}
	}

}
