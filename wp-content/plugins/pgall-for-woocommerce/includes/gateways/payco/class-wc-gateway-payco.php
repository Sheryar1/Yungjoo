<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	class WC_Gateway_Payco extends PAFW_Payment_Gateway {

		const PAY_METHOD_VBANK = '02';
		const PAY_METHOD_CARD = '31';
		const PAY_METHOD_SIMPLE_BANK = '35';
		const PAY_METHOD_PHONE = '60';
		const PAY_METHOD_PAYCO_POINT = '98';
		const PAY_METHOD_PAYCO_COUPON = '75';
		const PAY_METHOD_CARD_COUPON = '76';
		const PAY_METHOD_MALL_COUPON = '77';
		const PAY_METHOD_DEPOSIT_REFUND = '96';

		protected $key_for_test = array (
			'S0FSJE'
		);

		public function __construct() {
			parent::__construct();

			$this->master_id = 'payco';
			$this->pg_title     = __( 'NHN 페이코', 'pgall-for-woocommerce' );
			$this->method_title = __( 'NHN 페이코', 'pgall-for-woocommerce' );

			$this->supports[] = 'pafw-escrow-support-confirm-by-customer';
		}
		function __get( $key ) {
			$value = isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : '';

			return $value;
		}
		function is_vbank( $order = null ) {
			return self::PAY_METHOD_VBANK == pafw_get_meta( $order, '_pafw_payment_method' );
		}
		function is_escrow( $order = null ) {
			return in_array( pafw_get_meta( $order, '_pafw_payment_method' ), array ( self::PAY_METHOD_VBANK, self::PAY_METHOD_SIMPLE_BANK ) );
		}

		function get_payco_bill_url( $url ) {
			if ( 'sandbox' == $this->operation_mode ) {
				return 'https://alpha-api-bill.payco.com' . $url;
			} else {
				return 'https://api-bill.payco.com' . $url;
			}
		}

		public function get_transaction_url( $order ) {
			$return_url = '';

			if ( 'sandbox' == $this->operation_mode ) {
				$receipt_url = 'https://alpha-bill.payco.com/';
			} else {
				$receipt_url = 'https://bill.payco.com';
			}

			$seller_order_reference_key = pafw_get_meta( $order, '_pafw_txnid' );
			$order_no                   = $this->get_transaction_id( $order );

			if ( ! empty( $seller_order_reference_key ) ) {
				$return_url = sprintf( "%s/seller/receipt/%s/%s/%s", $receipt_url, $this->seller_key, $seller_order_reference_key, $order_no );
			}

			return apply_filters( 'woocommerce_get_transaction_url', $return_url, $order, $this );
		}
		function cancel_request( $order, $msg, $code = "1" ) {
			$params = array (
				'sellerKey'       => $this->seller_key,
				'orderNo'         => $this->get_transaction_id( $order ),
				'cancelTotalAmt'  => $order->get_total(),
				'orderCertifyKey' => pafw_get_meta( $order, '_pafw_order_certify_key' ),
				'requestMemo'     => $msg
			);

			$response = $this->call_api( $this->get_payco_bill_url( '/outseller/order/cancel' ), "json", urldecode( stripslashes( json_encode( $params ) ) ) );

			if ( 0 == $response['code'] ) {
				do_action( 'pafw_payment_action', 'cancelled', $order->get_total(), $order, $this );

				return "success";
			} else {
				throw new Exception( $response['message'], $response['code'] );
			}
		}
		function do_payment_response() {
			try {
				if ( empty( $_REQUEST['sellerOrderReferenceKey'] ) ) {
					throw new PAFW_Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '2001', 'PAFW-2001' );
				}

				$ids   = explode( '_', $_REQUEST['sellerOrderReferenceKey'] );
				$order = wc_get_order( $ids[0] );

				if ( ! $order || $_REQUEST['reserveOrderNo'] !== pafw_get_meta( $order, '_pafw_reserve_order_no' ) ) {
					throw new PAFW_Exception( __( '주문정보가 올바르지 않습니다.', 'pgall-for-woocommerce' ), '2002', 'PAFW-2002' );
				}

				$this->has_enough_stock( $order );

				if ( $order->get_total() != $_REQUEST['totalPaymentAmt'] ) {
					throw new PAFW_Exception( __( '주문금액과 결제금액이 틀립니다.', 'pgall-for-woocommerce' ), '2003', 'PAFW-2003' );
				}

				$approvalOrder["sellerKey"]               = $this->seller_key;
				$approvalOrder["reserveOrderNo"]          = $_REQUEST['reserveOrderNo'];
				$approvalOrder["paymentCertifyToken"]     = $_REQUEST['paymentCertifyToken'];
				$approvalOrder["sellerOrderReferenceKey"] = $_REQUEST['sellerOrderReferenceKey'];
				$approvalOrder["totalPaymentAmt"]         = $_REQUEST['totalPaymentAmt'];

				$response = $this->call_api( $this->get_payco_bill_url( '/outseller/payment/approval' ), "json", stripslashes( json_encode( $approvalOrder ) ) );

				$this->add_log( "결제 승인 결과\n" . print_r( array (
						'resultCode' => $response['code'],
						'resultMsg'  => $response['message']
					), true )
				);

				$this->add_log( print_r( $response, true ) );

				if ( $response && 0 == $response['code'] ) {
					$result = $response['result'];

					pafw_update_meta_data( $order, '_pafw_order_certify_key', $result['orderCertifyKey'] );
					pafw_update_meta_data( $order, "_pafw_payed_date", isset( $result['paymentCompleteYmdt'] ) ? $result['paymentCompleteYmdt'] : '' );
					pafw_update_meta_data( $order, "_pafw_txnid", $result['sellerOrderReferenceKey'] );
					pafw_update_meta_data( $order, "_pafw_total_price", $result['totalPaymentAmt'] );

					$payment_detail_info = array ();

					foreach ( $result['paymentDetails'] as $payment_detail ) {
						if ( self::PAY_METHOD_CARD == $payment_detail['paymentMethodCode'] ) {
							pafw_update_meta_data( $order, "_pafw_payment_method", $payment_detail['paymentMethodCode'] );
							$settle_info = $payment_detail['cardSettleInfo'];
							pafw_update_meta_data( $order, "_pafw_card_num", $settle_info['cardNo'] );
							pafw_update_meta_data( $order, "_pafw_card_code", $settle_info['cardCompanyCode'] );
							pafw_update_meta_data( $order, "_pafw_card_bank_code", $settle_info['cardCompanyCode'] );
							pafw_update_meta_data( $order, "_pafw_card_name", $settle_info['cardCompanyName'] );
							pafw_update_meta_data( $order, "_pafw_card_qouta", $settle_info['cardInstallmentMonthNumber'] );
							pafw_update_meta_data( $order, "_pafw_card_interest", $settle_info['cardInterestFreeYn'] );
							pafw_update_meta_data( $order, "_pafw_support_partial_cancel", $settle_info['partCancelPossibleYn'] );
						} else if ( self::PAY_METHOD_VBANK == $payment_detail['paymentMethodCode'] ) {
							pafw_update_meta_data( $order, "_pafw_payment_method", $payment_detail['paymentMethodCode'] );
							$settle_info = $payment_detail['nonBankbookSettleInfo'];
							pafw_update_meta_data( $order, '_pafw_vacc_num', $settle_info['accountNo'] );  //입금계좌번호
							pafw_update_meta_data( $order, '_pafw_vacc_bank_code', $settle_info['bankCode'] );    //입금은행코드
							pafw_update_meta_data( $order, '_pafw_vacc_bank_name', $settle_info['bankName'] );    //입금은행명/코드
							pafw_update_meta_data( $order, '_pafw_vacc_holder', '' );    //예금주
							pafw_update_meta_data( $order, '_pafw_vacc_depositor', '' );   //송금자
							pafw_update_meta_data( $order, '_pafw_vacc_date', $settle_info['paymentExpirationYmd'] );    //입금예정일
						} else if ( self::PAY_METHOD_PHONE == $payment_detail['paymentMethodCode'] ) {
							pafw_update_meta_data( $order, "_pafw_payment_method", $payment_detail['paymentMethodCode'] );
							$settle_info = $payment_detail['cellphoneSettleInfo'];
							pafw_update_meta_data( $order, "_pafw_hpp_num", $settle_info['cellphoneNo'] );
						} else if ( self::PAY_METHOD_SIMPLE_BANK == $payment_detail['paymentMethodCode'] ) {
							pafw_update_meta_data( $order, "_pafw_payment_method", $payment_detail['paymentMethodCode'] );
							$settle_info = $payment_detail['realtimeAccountTransferSettleInfo'];
							pafw_update_meta_data( $order, "_pafw_bank_code", $settle_info['bankCode'] );
							pafw_update_meta_data( $order, "_pafw_bank_name", $settle_info['bankName'] );
						}

						if ( is_callable( array ( $order, 'set_payment_method_title' ) ) ) {
							$order->set_payment_method_title( $this->title . ' - ' . $payment_detail['paymentMethodName'] );
						} else {
							pafw_update_meta_data( $order, '_payment_method_title', $this->title . ' - ' . $payment_detail['paymentMethodName'] );
						}

						$payment_detail_info[ $payment_detail['paymentMethodName'] ] = $payment_detail['paymentAmt'];
					}

					pafw_update_meta_data( $order, '_pafw_payment_details', $payment_detail_info );

					if ( 'Y' == $result['paymentCompletionYn'] ) {
						$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
							'거래번호' => $result['orderNo']
						) );

						$this->payment_complete( $order, $result['orderNo'] );
					} else {
						pafw_update_meta_data( $order, '_pafw_vacc_tid', $result['orderNo'] );

						$this->add_payment_log( $order, '[ 무통장 입금 대기중 ]', array (
							'거래번호' => $result['orderNo']
						) );

						if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
							pafw_reduce_order_stock( $order );
						}

						$order->update_status( 'on-hold', '무통장 입금을 기다려주세요.' );
					}

					$redirect_url = $order->get_checkout_order_received_url();
					ob_start();
					include( 'templates/payment_complete.php' );
					echo ob_get_clean();
					die();

				} else {
					throw new PAFW_Exception( $response['message'], '2004', $response['code'] );
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
						$order->update_status( 'failed', __( '페이코 결제내역을 확인하신 후, 고객에게 연락을 해주시기 바랍니다.', 'pgall-for-woocommerce' ) );
					}
				}

				do_action( 'pafw_payment_fail', $order, ! empty( $error_code ) ? $error_code : $e->getCode(), $e->getMessage() );

				throw $e;
			}
		}
		function do_deposit_noti() {
			try {
				$this->add_log( "do_deposit_noti()\n" . print_r( $_REQUEST, true ) );

				$response = json_decode( stripslashes( $_REQUEST['response'] ), true );

				if ( empty( $response['sellerOrderReferenceKey'] ) ) {
					throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ), '900001' );
				}

				$ids   = explode( '_', $response['sellerOrderReferenceKey'] );
				$order = wc_get_order( $ids[0] );

				if ( ! $order || $response['reserveOrderNo'] !== pafw_get_meta( $order, '_pafw_reserve_order_no' ) ) {
					throw new Exception( __( '주문정보가 올바르지 않습니다.', 'pgall-for-woocommerce' ), '900002' );
				}

				if ( $order->get_total() != $response['totalPaymentAmt'] ) {
					throw new Exception( __( '주문금액과 결제금액이 틀립니다.', 'pgall-for-woocommerce' ), '900003' );
				}

				if ( 'Y' == $response['paymentCompletionYn'] ) {
					pafw_update_meta_data( $order, "_pafw_payed_date", $response['paymentCompleteYmdt'] );
				}

				$payment_details = current( $response['paymentDetails'] );

				pafw_update_meta_data( $order, '_pafw_vbank_noti_received', 'yes' );
				pafw_update_meta_data( $order, '_pafw_vbank_noti_transaction_date', $payment_details['tradeYmdt'] );
				pafw_update_meta_data( $order, '_pafw_vbank_noti_deposit_bank', '' );
				pafw_update_meta_data( $order, '_pafw_vbank_noti_depositor', '' );

				$this->add_payment_log( $order, '[ 무통장 입금완료 ]', array (
					'입금시각' => preg_replace( '/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1-$2-$3 $4:$5:$6', $payment_details['tradeYmdt'] ),
					'결제번호' => $payment_details['paymentTradeNo']
				) );

				$order->payment_complete( $this->get_transaction_id( $order ) );

				//WC3.0 관련 가상계좌 입금통보시 결제 완료 시간 갱신 처리
				if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
					$order->set_date_paid( current_time( 'timestamp', true ) );
					$order->save();
				}

				do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );

				echo 'OK';
				exit();
			} catch ( Exception $e ) {
				$message = sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() );
				$this->add_log( $message );
				if ( $order ) {
					$order->add_order_note( $message );
				}
				echo 'FAIL';
				exit();
			}
		}
		function process_payment_response() {
			try {
				$this->add_log( 'Process Payment Response : ' . $_REQUEST['type'] );
				$this->add_log( print_r( $_REQUEST, true ) );
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
					case 'payment' :
						$this->do_payment_response();
						break;
					case 'deposit_noti' :
						$this->do_deposit_noti();
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

			wp_send_json_success( $result['order_sheet_url'] );
		}

		function process_order_pay() {
			wp_send_json_success( $this->process_payment( $_REQUEST['order_id'] ) );
		}
		function process_payment( $order_id ) {
			$this->add_log( 'Process Payment' );
			$this->add_log( print_r( $_REQUEST, true ) );

			$order = wc_get_order( $order_id );

			do_action( 'pafw_process_payment', $order );

			$date_limit = pafw_get( $this->settings, 'vbank_account_date_limit', 3 );

			$extra_data = array (
				'virtualAccountExpiryYmd' => date( 'Ymd', strtotime( current_time( 'mysql' ) . " +" . $date_limit . " days" ) ) . '235959',
				'viewOptions'             => array (
					'languageCode' => pafw_get( $this->settings, 'language_code', 'KR' )
				)
			);

			$params = array (
				'sellerKey'                   => $this->seller_key,
				'sellerOrderReferenceKey'     => $this->get_txnid( $order ),
				'sellerOrderReferenceKeyType' => 'UNIQUE_KEY',
				'orderTitle'                  => urlencode( $this->make_product_info( $order ) ),
				'totalPaymentAmt'             => $order->get_total(),
				'currency'                    => 'KRW',
				'returnUrl'                   => $this->get_api_url( 'payment' ),
				'nonBankbookDepositInformUrl' => $this->get_api_url( 'deposit_noti' ),
				'orderMethod'                 => is_user_logged_in() ? 'EASYPAY_F' : 'EASYPAY',
				'payMode'                     => 'PAY2',
				'orderProducts'               => $this->get_order_products( $order ),
				'extraData'                   => json_encode( $extra_data )
			);

			$response = $this->call_api( $this->get_payco_bill_url( '/outseller/order/reserve' ), "json", urldecode( json_encode( $params ) ) );

			if ( 0 == $response['code'] ) {
				pafw_update_meta_data( $order, '_pafw_reserve_order_no', $response['result']['reserveOrderNo'] );

				return array (
					'result'          => 'success',
					'order_sheet_url' => $response['result']['orderSheetUrl']
				);
			} else {
				$message = sprintf( "[결제오류] %s [%s]", $response['message'], $response['code'] );

				$this->add_log( $message . "\n" . print_r( $_REQUEST, true ) );
				$order->add_order_note( $message );

				do_action( 'pafw_payment_fail', $order, $response['code'], $response['message'] );

				wc_add_notice( $message, 'error' );
			}
		}
		function call_api( $url, $mode, $param ) {

			$this->add_log( "call_api $url Mode : $mode" );

			try {
				include( 'httpcurl.php' );

				$http = new HTTPCURL();
				$http->Post( $url, $mode, $param, "false" );
				$return_value = $http->getResult();
				$http->Close();

				$this->add_log( "API Result $url Status : " . $http->response['http_code'] );
			} catch ( RequestException $e ) {
				$this->add_log( "call_api function error : number - " . $e->getRequest() . ", description - " . $e->getResponse() );
				$this->add_log( print_r( $param, true ) );
				$this->add_log( print_r( $return_value, true ) );
			}

			return json_decode( $return_value, true );
		}

		function escrow_register_delivery_info() {

			$this->add_log( 'escrow_register_delivery_info' . print_r( $_REQUEST, true ) );

//			$order = wc_get_order( $_REQUEST['order_id'] );
			$order = $this->get_order();

			$params = array (
				'sellerKey'                      => $this->seller_key,
				'orderNo'                        => $this->get_transaction_id( $order ),
				'sellerOrderProductReferenceKey' => $_REQUEST['order_id'],
				'orderProductStatus'             => 'DELIVERY_START'
			);

			$this->add_log( 'updateOrderProductStatus : ' . print_r( $params, true ) );

			$response = $this->call_api( $this->get_payco_bill_url( '/outseller/order/updateOrderProductStatus' ), "json", urldecode( json_encode( $params ) ) );

			if ( 0 == $response['code'] ) {
				pafw_update_meta_data( $order, '_pafw_escrow_register_delivery_info', 'yes' );
				pafw_update_meta_data( $order, '_pafw_escrow_register_delivery_time', current_time( 'mysql' ) );
			} else {
				throw new Exception( sprintf( __( '배송등록중 오류가 발생했습니다. [%s] %s', 'pgall-for-woocommerce' ), $response['code'], $response['message'] ) );
			}

			wp_send_json_success( __( '배송등록이 처리되었습니다.', 'pgall-for-woocommerce' ) );
		}

		function escrow_purchase_decide() {
			$order = $this->get_order();

			$params = array (
				'sellerKey'                      => $this->seller_key,
				'orderNo'                        => $this->get_transaction_id( $order ),
				'sellerOrderProductReferenceKey' => $_REQUEST['order_id'],
				'orderProductStatus'             => 'PURCHASE_DECISION'
			);

			$this->add_log( 'updateOrderProductStatus : ' . print_r( $params, true ) );

			$response = $this->call_api( $this->get_payco_bill_url( '/outseller/order/updateOrderProductStatus' ), "json", urldecode( json_encode( $params ) ) );

			if ( 0 == $response['code'] ) {
				pafw_update_meta_data( $order, '_pafw_escrow_order_confirm', 'yes' );
				pafw_update_meta_data( $order, '_pafw_escrow_order_confirm_time', current_time( 'mysql' ) );
				$order->update_status( 'completed', __( '고객님이 구매확정을 하셨습니다.', 'pgall-for-woocommerce' ) );
			} else {
				throw new Exception( sprintf( __( '구매화정 처리중 오류가 발생했습니다. [%s] %s', 'pgall-for-woocommerce' ), $response['code'], $response['message'] ) );
			}

			wp_send_json_success( __( '구매확정이 차리되었습니다.', 'pgall-for-woocommerce' ) );

		}

		function add_meta_box_escrow( $post ) {
			$order = wc_get_order( $post );

			$order_status = $order->get_status();

			$is_payed               = ! empty( pafw_get_object_property( $order, 'paid_date' ) );
			$order_cancelled        = pafw_get_meta( $order, '_pafw_escrow_order_cancelled' );
			$register_delivery_info = 'yes' == pafw_get_meta( $order, '_pafw_escrow_register_delivery_info' );
			$is_cancelled           = 'yes' == pafw_get_meta( $order, '_pafw_escrow_order_cancelled' );
			$is_confirmed           = 'yes' == pafw_get_meta( $order, '_pafw_escrow_order_confirm' ) || 'yes' == pafw_get_meta( $order, '_pafw_escrow_order_confirm_reject' );

			include( 'views/escrow.php' );
		}
		function is_test_key() {
			return in_array( $this->seller_key, $this->key_for_test );
		}

		public function get_merchant_id() {
			return pafw_get( $this->settings, 'seller_key' );
		}
	}
}