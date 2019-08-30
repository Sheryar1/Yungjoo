<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Lguplus_Vbank' ) ) {

		class WC_Gateway_Lguplus_Vbank extends WC_Gateway_Lguplus {
			public function __construct() {
				$this->id = 'lguplus_vbank';

				parent::__construct();

				$this->settings['paymethod'] = 'SC0040';

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '가상계좌 무통장입금', 'pgall-for-woocommerce' );
					$this->description = __( '가상계좌 안내를 통해 무통장입금을 할 수 있습니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}
				$this->supports[] = 'pafw-vbank';
			}
			public function process_payment_success( $order, $xpay ) {
				pafw_update_meta_data( $order, '_pafw_vacc_tid', $xpay->Response( 'LGD_TID', 0 ) );
				pafw_update_meta_data( $order, '_pafw_vacc_num', $xpay->Response( 'LGD_ACCOUNTNUM', 0 ) );  //입금계좌번호
				pafw_update_meta_data( $order, '_pafw_vacc_bank_code', $xpay->Response( 'LGD_FINANCECODE', 0 ) );    //입금은행코드
				pafw_update_meta_data( $order, '_pafw_vacc_bank_name', mb_convert_encoding( $xpay->Response( 'LGD_FINANCENAME', 0 ), "UTF-8", "CP949" ) );    //입금은행명/코드
				pafw_update_meta_data( $order, '_pafw_vacc_holder', mb_convert_encoding( $xpay->Response( 'LGD_SAOWNER', 0 ), "UTF-8", "CP949" ) );    //예금주
				pafw_update_meta_data( $order, '_pafw_vacc_depositor', mb_convert_encoding( $xpay->Response( 'LGD_PAYER', 0 ), "UTF-8", "CP949" ) );   //송금자

				$this->add_payment_log( $order, '[ 가상계좌 입금 대기중 ]', array (
					'LG유플러스 거래번호' => $xpay->Response( 'LGD_TID', 0 ),
					'몰 고유 주문번호'   => $xpay->Response( 'LGD_OID', 0 )
				) );

				//가상계좌 주문 접수시 재고 차감여부 확인
				pafw_reduce_order_stock( $order );

				$order->update_status( $this->settings['order_status_after_vbank_payment'] );

				//WC 3.0 postmeta update 로 인해 별도로 가상계좌 추가 처리
				if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
					$order->set_date_paid( null );
					$order->save();
				}
			}
			function process_vbank_noti() {
				$REMOTE_IP = pafw_get( $_SERVER, 'HTTP_X_FORWARDED_FOR', $_SERVER['REMOTE_ADDR'] );
				$request_ip = ip2long( $REMOTE_IP );

				$this->add_log( '가상계좌 입금통보 시작 : ' . $REMOTE_IP );

				try {
					$valid = false;
					if ( ( $request_ip >= ip2long( '203.233.124.34' ) && $request_ip <= ip2long( '203.233.124.38' ) ) ||
					     ( $request_ip >= ip2long( '203.233.124.91' ) && $request_ip <= ip2long( '203.233.124.95' ) ) ||
					     ( $request_ip >= ip2long( '115.92.221.121' ) && $request_ip <= ip2long( '115.92.221.150' ) )
					) {
						$valid = true;
					}

					if ( ! $valid ) {
						throw new Exception( __( '[PAFW-ERR-8901] 잘못된 요청입니다. ', 'pgall-for-woocommerce' ) );
					}

					if ( empty( $_REQUEST['LGD_OID'] ) ) {
						throw new Exception( __( '[PAFW-ERR-8001] 잘못된 요청입니다. ', 'pgall-for-woocommerce' ) );
					}

					$ids      = explode( '_', $_REQUEST['LGD_OID'] );
					$order_id = intval( $ids[0] );
					$order    = wc_get_order( $order_id );

					if ( ! $order ) {
						throw new Exception( __( '[PAFW-ERR-8002] 잘못된 요청입니다. ', 'pgall-for-woocommerce' ) );
					}

					if ( in_array( $order->get_status(), array ( 'completed', 'cancelled', 'refunded' ) ) ) {
						throw new Exception( sprintf( __( '[PAFW-ERR-8003] 입금통보 내역이 수신되었으나, 주문 상태에 문제가 있습니다. 이미 완료된 주문이거나, 환불된 주문일 수 있습니다. 상점거래번호(OID) : %s', 'pgall-for-woocommerce' ), $_REQUEST['LGD_OID'] ) );
					}

					if ( $_REQUEST['LGD_TID'] != $this->get_transaction_id( $order ) ) {
						throw new Exception( __( '[PAFW-ERR-8004] TID 불일치', 'pgall-for-woocommerce' ) );
					}

					if ( $_REQUEST['LGD_OID'] != pafw_get_meta( $order, '_pafw_txnid' ) ) {
						throw new Exception( __( '[PAFW-ERR-8004] 거래번호 불일치', 'pgall-for-woocommerce' ) );
					}

					if ( $_REQUEST['LGD_FINANCECODE'] != pafw_get_meta( $order, '_pafw_vacc_bank_code' ) ) {
						throw new Exception( __( '[PAFW-ERR-8004] 입금은행 불일치', 'pgall-for-woocommerce' ) );
					}

					if ( $_REQUEST['LGD_ACCOUNTNUM'] != pafw_get_meta( $order, '_pafw_vacc_num' ) ) {
						throw new Exception( __( '[PAFW-ERR-8004] 입금계좌번호 불일치', 'pgall-for-woocommerce' ) );
					}

					if ( $_REQUEST['LGD_AMOUNT'] != pafw_get_meta( $order, '_pafw_total_price' ) ) {
						throw new Exception( __( '[PAFW-ERR-8004] 입금액 불일치', 'pgall-for-woocommerce' ) );
					}

					pafw_update_meta_data( $order, '_pafw_vbank_noti_received', 'yes' );
					pafw_update_meta_data( $order, '_pafw_vbank_noti_transaction_date', $_REQUEST['LGD_PAYDATE'] );
					pafw_update_meta_data( $order, '_pafw_cash_receipts', isset ( $_REQUEST['LGD_CASSEQNO'] ) ? $_REQUEST['LGD_CASSEQNO'] : '' );

					$order->add_order_note( sprintf( __( '가상계좌 무통장 입금이 완료되었습니다.  거래번호(TID) : %s, 상점거래번호(OID) : %s', 'pgall-for-woocommerce' ), $_REQUEST['LGD_TID'], $_REQUEST['LGD_OID'] ) );
					$this->add_log( sprintf( __( '가상계좌 무통장 입금이 완료되었습니다.  거래번호(TID) : %s, 상점거래번호(OID) : %s', 'pgall-for-woocommerce' ), $_REQUEST['LGD_TID'], $_REQUEST['LGD_OID'] ) );
					$order->payment_complete( $_REQUEST['LGD_TID'] );
					$order->update_status( $this->settings['order_status_after_payment'] );

					//WC3.0 관련 가상계좌 입금통보시 결제 완료 시간 갱신 처리
					if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
						$order->set_date_paid( current_time( 'timestamp', true ) );
						$order->save();
					}

					do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );

					echo 'OK';
					exit();

				} catch ( Exception $e ) {
					if ( $order ) {
						$order->add_order_note( $e->getMessage() );
					}
					$this->add_log( $e->getMessage() );
					$this->add_log( print_r( $_REQUEST, true ) );
					echo 'OK';    //가맹점 관리자 사이트에서 재전송 가능하나 주문건 확인 필요
					exit();
				}
			}

			function get_cash_receipts( $order ) {
				$cash_receipts = pafw_get_meta( $order, '_pafw_cash_receipts' );

				return '000' == $cash_receipts || '' == $cash_receipts ? '미발행' : '발행';
			}
		}
	}

} // class_exists function end