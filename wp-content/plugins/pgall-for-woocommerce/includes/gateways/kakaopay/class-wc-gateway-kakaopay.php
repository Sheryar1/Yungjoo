<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	require_once( 'class-pafw-kakaopay-api.php' );

	class WC_Gateway_KakaoPay extends PAFW_Payment_Gateway {

		protected $key_for_test = array (
			'TC0ONETIME',
			'TCSUBSCRIP'
		);

		public function __construct() {
			$this->master_id = 'kakaopay';
			$this->pg_title     = __( '카카오페이', 'pgall-for-woocommerce' );
			$this->method_title = __( '카카오페이', 'pgall-for-woocommerce' );

			parent::__construct();
		}
		function __get( $key ) {
			$value = isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : '';

			return $value;
		}
		function is_vbank( $order = null ) {
			return false;
		}

		function calculate_tax( $amount ) {
			$amount = intval( $amount );

			return $amount - round( $amount / 1.1 );
		}

		public function get_transaction_url( $order ) {
			if ( 'TC0ONETIME' == $this->cid ) {
				$transaction_url = 'https://mockup-pg-web.kakao.com/v1/confirmation/p/' . $this->get_transaction_id( $order ) . '/';
			} else {
				$transaction_url = 'https://pg-web.kakao.com/v1/confirmation/p/' . $this->get_transaction_id( $order ) . '/';
			}

			$hash = hash( 'sha256', $this->cid . $this->get_transaction_id( $order ) . pafw_get_object_property( $order, 'id' ) . $order->get_user_id() );

			return $transaction_url . $hash;
		}
		function cancel_request( $order, $msg, $code = "1" ) {
			$params = array (
				'cid'                    => $this->cid,
				'tid'                    => $this->get_transaction_id( $order ),
				'cancel_amount'          => $order->get_total(),
				'cancel_tax_free_amount' => 0,
				'cancel_vat_amount'      => $this->calculate_tax( $order->get_total() )
			);

			$response = $this->call_api( 'cancel', $params );

			if ( $response && ! empty( $response['aid'] ) ) {

				do_action( 'pafw_payment_action', 'cancelled', $order->get_total(), $order, $this );

				if ( class_exists( 'WC_Subscriptions_Manager' ) ) {
					WC_Subscriptions_Manager::cancel_subscriptions_for_order( $order );
				}

				return "success";
			} else {
				throw new Exception( $response['msg'], $response['code'] );
			}
		}
		function update_payment_method( $subscription ) {
			$subscription->set_payment_method( $this );
			$subscription->save();

			$redirect_url = $subscription->get_checkout_order_received_url();
			ob_start();
			include( 'templates/payment_complete.php' );
			echo ob_get_clean();
			die();
		}
		function do_approval() {
			try {
				if ( empty( $_REQUEST['pg_token'] ) || empty( $_REQUEST['order_id'] ) ) {
					throw new PAFW_Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '2001', 'PAFW-2001' );
				}

				$order = wc_get_order( $_REQUEST['order_id'] );


				if ( ! $order ) {
					throw new PAFW_Exception( __( '주문정보가 올바르지 않습니다.', 'pgall-for-woocommerce' ), '2002', 'PAFW-2002' );
				}

				$params = array (
					'cid'              => $this->cid,
					'tid'              => pafw_get_meta( $order, '_pafw_kakaopay_tid' ),
					'partner_order_id' => $_REQUEST['order_id'],
					'partner_user_id'  => $order->get_user_id(),
					'pg_token'         => $_REQUEST['pg_token']
				);

				$response = $this->call_api( 'approve', $params );

				$this->add_log( "결제 승인 결과\n" . print_r( $response, true ) );

				if ( empty( $response ) ) {
					throw new PAFW_Exception( __( '결제 승인 과정에서 오류가 발생했습니다.' ), '9001' );
				}

				if ( ! empty( $response['aid'] ) ) {
					if ( function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order ) ) {
						if ( $this->id == $order->get_payment_method() ) {
							$this->cancel_batch_key( $order );
						}

						WC_Subscriptions_Change_Payment_Gateway::update_payment_method( $order, $this->id );

						pafw_update_meta_data( $order, '_pafw_subscription_batch_key', $response['sid'] );

						$this->add_payment_log( $order, '[ 결제 수단 변경 ]', array (
							'결제수단'     => $this->title . ' - ' . $response['payment_method_type'],
							'거래요청번호'   => $response['aid'],
							'정기결제 배치키' => $response['sid']
						) );

						$redirect_url = $order->get_view_order_url();
					} else {
						pafw_update_meta_data( $order, "_pafw_payment_method", $response['payment_method_type'] );
						pafw_update_meta_data( $order, '_pafw_aid', $response['aid'] );
						pafw_update_meta_data( $order, "_pafw_txnid", $response['tid'] );
						pafw_update_meta_data( $order, "_pafw_payed_date", $response['approved_at'] );
						pafw_update_meta_data( $order, "_pafw_total_price", $response['amount']['total'] );

						if ( is_callable( array ( $order, 'set_payment_method_title' ) ) ) {
							$order->set_payment_method_title( $this->title . ' - ' . $response['payment_method_type'] );
						} else {
							pafw_update_meta_data( $order, '_payment_method_title', $this->title . ' - ' . $response['payment_method_type'] );
						}
						if ( $this->supports( 'subscriptions' ) ) {
							pafw_update_meta_data( $order, '_pafw_subscription_batch_key', $response['sid'] );

							$subscriptions = wcs_get_subscriptions_for_order( pafw_get_object_property( $order, 'id' ), array ( 'order_type' => 'any' ) );

							foreach ( $subscriptions as $subscription ) {
								pafw_update_meta_data( $subscription, '_pafw_subscription_batch_key', $response['sid'] );
							}
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

						if ( $this->supports( 'subscriptions' ) ) {
							$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
								'거래요청번호'   => $response['aid'],
								'정기결제 배치키' => $response['sid']
							) );
						} else {
							$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
								'거래요청번호' => $response['aid']
							) );
						}

						$this->payment_complete( $order, $response['tid'] );

						$redirect_url = $order->get_checkout_order_received_url();
					}

					ob_start();
					include( 'templates/payment_complete.php' );
					echo ob_get_clean();
					die();

				} else {
					$message = sprintf( '( %s ) %s', $response['code'], pafw_get( $response, 'message', pafw_get( $response, 'msg' ) ) );
					if ( ! empty( $response['extras'] ) ) {
						$message .= sprintf( ' ( %s ) %s', pafw_get( $response['extras'], 'method_result_code' ), pafw_get( $response['extras'], 'method_result_message' ) );
					}

					throw new PAFW_Exception( $message, '2004', $response['code'] );
				}
			} catch ( Exception $e ) {

				$error_code = '';
				if ( $e instanceof PAFW_Exception ) {
					$error_code = $e->getErrorCode();
				}

				$message = sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() );

				if ( $order ) {
					$order->add_order_note( $message );
					if ( empty( pafw_get_object_property( $order, 'paid_date' ) ) ) {
						$order->update_status( 'failed', __( 'KakaoPay 결제내역을 확인하신 후, 고객에게 연락을 해주시기 바랍니다.', 'pgall-for-woocommerce' ) );
					}
				}

				do_action( 'pafw_payment_fail', $order, ! empty( $error_code ) ? $error_code : $e->getCode(), $e->getMessage() );

				throw $e;
			}
		}

		function do_cancel() {
			$this->add_log( 'Cancelled by user' );

			do_action( 'pafw_payment_cancel' );

			$redirect_url = wc_get_page_permalink( 'checkout' );

			ob_start();
			include( 'templates/cancel_by_user.php' );
			echo ob_get_clean();
			die();
		}

		function do_fail( $order ) {
			$this->add_log( 'Failed by timeout.' );

			$order = wc_get_order( $_REQUEST['order_id'] );

			do_action( 'pafw_payment_fail', $order, '8001', __( '결제제한 시간(15분)이 초과되었습니다.', 'pgall-for-woocommerce' ) );

			$redirect_url = wc_get_page_permalink( 'checkout' );

			ob_start();
			include( 'templates/failed_by_timeout.php' );
			echo ob_get_clean();
			die();
		}
		function process_payment_response() {
			try {
				$this->add_log( 'Process Payment Response : ' . $_REQUEST['type'] );
				$result_code = $_REQUEST['code'];
				if ( $result_code == 2222 ) {
					$this->add_log( 'Cancelled by user' );

					do_action( 'pafw_payment_cancel' );

					$redirect_url = wc_get_page_permalink( 'checkout' );

					ob_start();
					include( 'templates/cancel_by_user.php' );
					echo ob_get_clean();
					die();
				}

				if ( empty( $_REQUEST['type'] ) ) {
					throw new PAFW_Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '1001', 'PAFW-1001' );
				}

				switch ( $_REQUEST['type'] ) {
					case 'approval' :
						$this->do_approval();
						break;
					case 'fail' :
						$this->do_fail();
						break;
					case 'cancel' :
						$this->do_cancel();
						break;
				}

			} catch ( Exception $e ) {
				$error_code = '';
				if ( $e instanceof PAFW_Exception ) {
					$error_code = $e->getErrorCode();
				}

				$message = sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() );
				$this->add_log( "[오류] " . $message . "\n" . print_r( $_REQUEST, true ) );

				wc_add_notice( $message, 'error' );

				$error_message = $message;
				$redirect_url  = wc_get_page_permalink( 'checkout' );
				ob_start();
				include( 'templates/payment_error.php' );
				echo ob_get_clean();
				die();
			}
		}
		function get_order_products( $order ) {
			return array (
				array (
					'cpId'                           => $this->cpid,
					'productId'                      => $this->product_id,
					'productAmt'                     => $order->get_total(),
					'productPaymentAmt'              => $order->get_total(),
					'orderQuantity'                  => 1,
					'sortOrdering'                   => 1,
					'productName'                    => urlencode( $this->make_product_info( $order ) ),
					'sellerOrderProductReferenceKey' => pafw_get_object_property( $order, 'id' )
				)
			);
		}

		function get_order_sheet_url() {
			$order = $this->get_order();

			pafw_set_browser_information( $order );

			$result = self::process_payment( pafw_get_object_property( $order, 'id' ) );

			wp_send_json_success( $result['next_redirect_url'] );
		}

		function process_order_pay() {
			wp_send_json_success( $this->process_payment( $_REQUEST['order_id'] ) );
		}
		function process_payment( $order_id ) {
			$this->add_log( 'Process Payment' );

			$order = wc_get_order( $order_id );

			do_action( 'pafw_process_payment', $order );

			if ( function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order ) ) {
				$total_amount = 0;
				$vat_amount   = 0;
			} else {
				$total_amount = $order->get_total();
				$vat_amount   = $this->calculate_tax( $total_amount );
				$order->set_payment_method( $this );

				if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
					$order->save();
				}
			}

			$params = array (
				'cid'              => $this->cid,
				'partner_order_id' => $order_id,
				'partner_user_id'  => $order->get_user_id(),
				'item_name'        => $this->make_product_info( $order ),
				'quantity'         => $order->get_item_count(),
				'total_amount'     => $total_amount,
				'tax_free_amount'  => 0,
				'vat_amount'       => $vat_amount,
				'approval_url'     => PAFW_KakaoPay_API::make_api_url( 'cb_approval', $this->get_api_url( 'approval&order_id=' . $order_id ) ),
				'cancel_url'       => PAFW_KakaoPay_API::make_api_url( 'cb_cancel', $this->get_api_url( 'cancel&order_id=' . $order_id ) ),
				'fail_url'         => PAFW_KakaoPay_API::make_api_url( 'cb_fail', $this->get_api_url( 'fail&order_id=' . $order_id ) )
			);

			$response = $this->call_api( 'ready', $params );

			if ( $response && ! empty( $response['tid'] ) ) {
				pafw_update_meta_data( $order, '_pafw_kakaopay_tid', $response['tid'] );
				pafw_update_meta_data( $order, '_pafw_created_at', $response['created_at'] );

				return array (
					'result'            => 'success',
					'next_redirect_url' => wp_is_mobile() ? $response['next_redirect_mobile_url'] : $response['next_redirect_pc_url']
				);
			} else {
				$message = sprintf( "[결제오류] %s [%s]", $response['message'], $response['code'] );

				$this->add_log( $message . "\n" . print_r( $_REQUEST, true ) );
				$order->add_order_note( $message );

				do_action( 'pafw_payment_fail', $order, $response['code'], $response['message'] );

				wc_add_notice( $message, 'error' );
			}
		}
		function call_api( $action, $params ) {
			if ( 'yes' == $this->settings['use_store_application'] && ! empty( $this->settings['admin_key'] ) ) {
				$cl = curl_init();

				curl_setopt( $cl, CURLOPT_URL, 'https://kapi.kakao.com/v1/payment/' . $action );
				curl_setopt( $cl, CURLOPT_CONNECTTIMEOUT, 10 );
				curl_setopt( $cl, CURLOPT_TIMEOUT, 10 );
				curl_setopt( $cl, CURLOPT_HTTPHEADER, array ( 'Authorization: KakaoAK ' . $this->settings['admin_key'] ) );
				curl_setopt( $cl, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $cl, CURLOPT_FOLLOWLOCATION, true );
				curl_setopt( $cl, CURLOPT_MAXREDIRS, 5 );
				curl_setopt( $cl, CURLOPT_POST, 1 );
				curl_setopt( $cl, CURLOPT_POSTFIELDS, http_build_query( $params ) );

				$result = json_decode( curl_exec( $cl ), true );

				curl_close( $cl );
			} else {
				$result = PAFW_KakaoPay_API::call( $action, array (
					'params' => json_encode( $params )
				) );
			}

			return $result;
		}
		function do_repay( $order, $amount, $already_refunded = false ) {
			$params = array (
				'cid'                    => $this->cid,
				'tid'                    => $this->get_transaction_id( $order ),
				'cancel_amount'          => $amount,
				'cancel_tax_free_amount' => 0,
				'cancel_vat_amount'      => $this->calculate_tax( $amount ),
			);

			$this->add_log( "부분취소 시작 \n" . print_r( $params, true ) );

			$response = $this->call_api( 'cancel', $params );

			$this->add_log( "부분취소 응답 \n" . print_r( $response, true ) );

			if ( $response && ! empty( $response['aid'] ) ) {

				do_action( 'pafw_payment_action', 'cancelled', $amount, $order, $this );

				$refund_reason = __( '관리자의 요청에 의한 부분취소', 'pgall-for-woocommerce' );

				if ( ! $already_refunded ) {
					//부분 환불 처리
					$refund = wc_create_refund( array (
						'amount'     => $amount,
						'reason'     => $refund_reason,
						'order_id'   => pafw_get_object_property( $order, 'id' ),
						'line_items' => array (),
					) );
				}

				//부분취소후 재승인 금액이 0원인 경우 모든 금액을 부분환불 처리한 것으로 이경우 환불됨 상태로 변경처리.
				if ( 0 == $response['cancel_available_amount']['total'] ) {
					$order->update_status( 'refunded' );
					pafw_update_meta_data( $order, '_pafw_order_cancelled', 'yes' );
					pafw_update_meta_data( $order, '_pafw_cancel_date', current_time( 'mysql' ) );
				}

				//부분환불 정보 확인
				$repay_info = pafw_get_meta( $order, '_pafw_repay' );
				$repay_info = json_decode( $repay_info, true );

				if ( ! empty( $repay_info ) ) {
					//부분환불 정보가 있음. 기존 정보에 추가하여 처리
					$repay_cnt = count( $repay_info );

					$repay_info[ ( $repay_cnt + 1 ) ] = array (
						'aid'             => $response['aid'],
						'canceled_amount' => $response['canceled_amount']
					);
				} else {
					$repay_info[1] = array (
						'aid'             => $response['aid'],
						'canceled_amount' => $response['canceled_amount']
					);
				}

				$this->add_payment_log( $order, '[ 부분 취소 성공 ]', array (
					'거래요청번호' => $response['aid'],
					'취소금액'   => wc_price( $amount )
				) );

				pafw_update_meta_data( $order, '_pafw_repay', json_encode( $repay_info, JSON_UNESCAPED_UNICODE ) );
			} else {
				$message = sprintf( '[%d] %s', $response['code'], $response['msg'] );

				$this->add_payment_log( $order, '[ 부분 취소 실패 ]', array (
					'CODE'    => $response['code'],
					'MESSAGE' => $response['msg']
				), false );

				throw new Exception( $message );
			}
		}
		function repay_request() {
			if ( ! current_user_can( 'publish_shop_orders' ) ) {
				throw new Exception( __( '주문 관리 권한이 없습니다.', 'pgall-for-woocommerce' ) );
			}

			$order = $this->get_order();

			$amount = isset( $_REQUEST['amount'] ) ? intval( $_REQUEST['amount'] ) : '';

			if ( $amount <= 0 ) {
				throw new Exception( __( '환불 금액은 0보다 커야합니다.', 'pgall-for-woocommerce' ) );
			}

			//부분취소 요청
			$this->do_repay( $order, $amount );

			wp_send_json_success( __( '부분환불이 정상적으로 처리되었습니다. 주문 메모 내용을 확인해 주세요.', 'pgall-for-woocommerce' ) );
		}

		function can_repay( $order, $card_code, $card_bank_code, $repay_count ) {
			if ( ! $this->is_refundable( $order ) ) {
				return new WP_Error( 'ERR-PAFW-PR', '부분취소 불가능 주문상태' );
			}

			return true;
		}
		function add_meta_box_repay( $post ) {
			$order = wc_get_order( $post );

			$repay_info     = pafw_get_meta( $order, '_pafw_repay' );
			$repay_cnt      = count( json_decode( $repay_info, true ) );
			$card_code      = pafw_get_meta( $order, '_pafw_card_code' );
			$card_bank_code = pafw_get_meta( $order, '_pafw_card_bank_code' );
			$can_repay = $this->can_repay( $order, $card_code, $card_bank_code, $repay_cnt );

			include_once( 'views/repay.php' );
		}
		public function add_meta_boxes( $order ) {
			parent::add_meta_boxes( $order );

			if ( $this->supports( 'pafw_additional_charge' ) ) {
				add_meta_box(
					'pafw-order-additional-charge',
					__( '카카오페이 추가과금 <span class="pafw-powerd"><a target="_blank" href="https://www.codemshop.com/">Powered by CodeM</a></span>', 'pgall-for-woocommerce' ),
					array ( $this, 'add_meta_box_additional_charge' ),
					'shop_order',
					'side',
					'high'
				);
			}

			if ( $this->is_refundable( $order ) && ! in_array( $order->get_status(), apply_filters( 'woocommerce_valid_order_statuses_for_payment', array ( 'on-hold', 'pending', 'failed' ), $order ) ) ) {
				add_meta_box(
					'pafw-order-repay',
					__( '카카오페이 부분환불 <span class="pafw-powerd"><a target="_blank" href="https://www.codemshop.com/">Powered by CodeM</a></span>', 'pgall-for-woocommerce' ),
					array ( $this, 'add_meta_box_repay' ),
					'shop_order',
					'side',
					'high'
				);
			}
		}

		public function is_fully_refundable( $order, $screen = 'admin' ) {
			$repay_info = json_decode( pafw_get_meta( $order, '_pafw_repay' ), true );

			return $this->is_refundable( $order, $screen ) && empty( $repay_info );
		}
		function is_test_key() {
			return in_array( pafw_get( $this->settings, 'cid' ), $this->key_for_test );
		}

		public function get_merchant_id() {
			return pafw_get( $this->settings, 'cid' );
		}
	}
}