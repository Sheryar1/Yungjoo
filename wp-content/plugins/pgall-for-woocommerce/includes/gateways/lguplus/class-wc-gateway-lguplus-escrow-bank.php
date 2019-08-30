<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Lguplus_Escrow_Bank' ) ) {

		class WC_Gateway_Lguplus_Escrow_Bank extends WC_Gateway_Lguplus {
			public function __construct() {
				$this->id = 'lguplus_escrow_bank';

				parent::__construct();

				$this->method_title = __( '실시간계좌이체(에스크로)', 'pgall-for-woocommerce' );

				$this->settings['paymethod'] = 'SC0030';

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '실시간계좌이체(에스크로)', 'pgall-for-woocommerce' );
					$this->description = __( '에스크로 방식으로 계좌에서 바로 결제하는 에스크로 실시간 계좌이체 입니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}
				$this->supports[] = 'pafw-escrow';
			}
			public function process_payment_success( $order, $xpay ) {
				pafw_update_meta_data( $order, '_pafw_bank_code', $xpay->Response( 'LGD_FINANCECODE', 0 ) );    //입금은행코드
				pafw_update_meta_data( $order, '_pafw_bank_name', mb_convert_encoding( $xpay->Response( 'LGD_FINANCENAME', 0 ), "UTF-8", "CP949" ) );    //입금은행명/코드
				pafw_update_meta_data( $order, '_pafw_cash_receipts', $xpay->Response( 'LGD_CASHRECEIPTNUM', 0 ) );

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
					'LG유플러스 거래번호' => $xpay->Response( 'LGD_TID', 0 ),
					'몰 고유 주문번호'   => $xpay->Response( 'LGD_OID', 0 )
				) );
			}

			function get_register_delivery_url() {
				if ( 'production' == $this->operation_mode ) {
					return 'http://pgweb.uplus.co.kr/pg/wmp/mertadmin/jsp/escrow/rcvdlvinfo.jsp';
				} else {
					return 'http://pgweb.uplus.co.kr:7085/pg/wmp/mertadmin/jsp/escrow/rcvdlvinfo.jsp';
				}
			}
			function escrow_register_delivery_info() {
				$this->check_shop_order_capability();

				$order = $this->get_order();
				$escrow_type     = isset( $_REQUEST['escrow_type'] ) ? $_REQUEST['escrow_type'] : '';
				$tracking_number = isset( $_REQUEST['tracking_number'] ) ? $_REQUEST['tracking_number'] : '';

				if ( empty( $tracking_number ) || empty( $escrow_type ) ) {
					throw new Exception( __( '필수 파라미터가 누락되었습니다.', 'pgall-for-woocommerce' ) );
				}

				$url = $this->get_register_delivery_url();

				$mid         = ( 'sandbox' == $this->operation_mode ? 't' : '' ) . $this->merchant_id;
				$oid         = pafw_get_meta( $order, '_pafw_txnid' );
				$dlvdate     = date( 'YmdHi', strtotime( current_time( 'mysql' ) ) );
				$dlvcompcode = $this->delivery_company_name;
				$dlvno       = $tracking_number;
				$mertkey      = $this->merchant_key;

				$hashdata = MD5( $mid . $oid . $dlvdate . $dlvcompcode . $dlvno . $mertkey );

				$params = array (
					'mid'          => $mid,
					'oid'          => $oid,
					'dlvtype'      => '03',
					'dlvdate'      => $dlvdate,
					'dlvcompcode'  => $dlvcompcode,
					'dlvno'        => $tracking_number,
					'dlvworker'    => $this->delivery_sender_name,
					'dlvworkertel' => $this->delivery_sender_phone,
					'hashdata'     => $hashdata
				);

				$response = wp_remote_post( $url, array (
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array (),
					'body'        => $params,
					'cookies'     => array ()
				) );

				if ( 0 === strpos( 'OK', trim( $response['body'] ) ) ) {
					pafw_update_meta_data( $order, '_pafw_escrow_tracking_number', $tracking_number );
					pafw_update_meta_data( $order, '_pafw_escrow_register_delivery_info', 'yes' );
					pafw_update_meta_data( $order, '_pafw_escrow_register_delivery_time', current_time( 'mysql' ) );

					$order->add_order_note( __( '판매자님께서 고객님의 에스크로 결제 주문을 배송 등록 또는 수정 처리하였습니다.', 'pgall-for-woocommerce' ), true );
					$order->update_status( $this->order_status_after_enter_shipping_number );
				} else {
					throw new Exception( sprintf( __( '배송등록중 오류가 발생했습니다. %s', 'pgall-for-woocommerce' ), mb_convert_encoding( trim( $response['body'] ), "UTF-8", "EUC-KR" ) ) );
				}

				wp_send_json_success( __( '배송등록이 처리되었습니다.', 'pgall-for-woocommerce' ) );
			}

		}

	}

} // class_exists function end