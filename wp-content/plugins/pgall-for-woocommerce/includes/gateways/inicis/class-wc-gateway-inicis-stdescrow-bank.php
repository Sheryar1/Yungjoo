<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Inicis_StdEscrow_Bank' ) ) {

		class WC_Gateway_Inicis_StdEscrow_Bank extends WC_Gateway_Inicis {

			public function __construct() {

				$this->id = 'inicis_stdescrow_bank';

				parent::__construct();

				$this->settings['quotabase']   = '';
				$this->settings['nointerest']  = '';
				$this->settings['gopaymethod'] = 'directbank';
				$this->settings['paymethod']   = 'bank';

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '에스크로', 'pgall-for-woocommerce' );
					$this->description = __( '이니시스 결제대행사를 통해 결제합니다. 에스크로 결제의 경우 인터넷익스플로러(IE) 환경이 아닌 경우 사용이 불가능합니다. 결제 완료시 내 계정(My-Account)에서 주문을 확인하여 주시기 바랍니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}
				$this->supports[] = 'pafw-escrow';
				$this->supports[] = 'pafw-escrow-support-confirm-by-customer';
			}

			public function get_merchant_id() {
				return pafw_get( $this->settings, 'escrow_merchant_id' );
			}

			function __get( $key ) {
				if ( 'merchant_id' == $key || 'signkey' == $key ) {
					$key = 'escrow_' . $key;

					$value = isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : '';

					if ( empty( $value ) && 'escrow_signkey' == $key ) {
						$value = 'SU5JTElURV9UUklQTEVERVNfS0VZU1RS';  //INIpayTest 기본값
					}

					return $value;
				}

				return WC_Gateway_Inicis::__get( $key );
			}
			function process_standard( $order, $result_map ) {
				pafw_update_meta_data( $order, '_pafw_bank_code', $result_map['ACCT_BankCode']);
				pafw_update_meta_data( $order, '_pafw_bank_name', $this->get_bank_name( $result_map['ACCT_BankCode'] ) );
				pafw_update_meta_data( $order, '_pafw_cash_receipts', isset ( $result_map['CSHR_Type'] ) ? $result_map['CSHR_Type'] : '' );

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
					'이니시스 거래번호' => $result_map['tid'],
					'몰 고유 주문번호' => $result_map['MOID']
				) );
			}
			function process_mobile_noti( $order = null ) {
				pafw_update_meta_data( $order, '_pafw_bank_code', $_REQUEST['P_FN_CD1'] );
				pafw_update_meta_data( $order, '_pafw_bank_name', $this->get_bank_name( $_REQUEST['P_FN_CD1'] ) );
				pafw_update_meta_data( $order, '_pafw_cash_receipts', isset ( $_REQUEST['P_CSHR_TYPE'] ) ? $_REQUEST['P_CSHR_TYPE'] : '' );

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
					'이니시스 거래번호' => $_REQUEST['P_TID'],
					'몰 고유 주문번호' => $_REQUEST['P_OID']
				) );
			}

			function is_fully_refundable( $order, $screen = 'admin' ) {
				$register_delivery_info = pafw_get_meta( $order, '_pafw_escrow_register_delivery_info' );

				return parent::is_fully_refundable( $order, $screen ) && 'yes' != $register_delivery_info;
			}

			function get_cash_receipts( $order ) {
				$cash_receipts = pafw_get_meta( $order, '_pafw_cash_receipts' );

				return '' == $cash_receipts ? '미발행' : '발행';
			}
			function escrow_purchase_decide() {
				$order = $this->get_order();

				if ( $_REQUEST['order_key'] != pafw_get_object_property( $order, 'order_key' ) ) {
					throw new Exception( __( '잘못된 주문정보입니다.', 'pgall-for-woocommerce' ) );
				}

				$escrow_params = array ();
				parse_str( $_REQUEST['params'], $escrow_params );

				if ( version_compare( PHP_VERSION, '7.1.0' ) < 0 ) {
					require_once( PAFW()->plugin_path() . "/lib/inicis/inipay50/INILib.php" );
				} else {
					require_once( PAFW()->plugin_path() . "/lib/inicis/inipay50_71/INILib.php" );
				}

				$iniescrow = new INIpay50();

				$iniescrow->SetField( "inipayhome", $this->settings['libfolder'] );       // 이니페이 홈디렉터리(상점수정 필요)
				$iniescrow->SetField( "tid", $escrow_params['tid'] );                                          // 거래아이디
				$iniescrow->SetField( "mid", $escrow_params['mid'] );                                          // 상점아이디
				$iniescrow->SetField( "admin", "1111" );                                               // 키패스워드(상점아이디에 따라 변경)
				$iniescrow->SetField( "type", "escrow" );                                             // 고정 (절대 수정 불가)
				$iniescrow->SetField( "escrowtype", "confirm" );                                      // 고정 (절대 수정 불가)
				$iniescrow->SetField( "debug", "false" );                                               // 로그모드("true"로 설정하면 상세한 로그가 생성됨)
				$iniescrow->SetField( "encrypted", $escrow_params['encrypted'] );
				$iniescrow->SetField( "sessionkey", $escrow_params['sessionkey'] );

				$iniescrow->startAction();

				$tid        = $iniescrow->GetResult( "tid" );                  // 거래번호
				$resultCode = $iniescrow->GetResult( "ResultCode" );           // 결과코드 ("00"이면 지불 성공)
				$resultMsg  = $iniescrow->GetResult( "ResultMsg" );            // 결과내용 (지불결과에 대한 설명)
				$resultDate = $iniescrow->GetResult( "CNF_Date" );             // 처리 날짜 (구매확인일경우)
				$resultTime = $iniescrow->GetResult( "CNF_Time" );             // 처리 시각 (구매확인일경우)

				if ( $resultDate == "" ) {
					$resultDate = $iniescrow->GetResult( "DNY_Date" );         // 처리 날짜 (구매거절일경우)
					$resultTime = $iniescrow->GetResult( "DNY_Time" );         // 처리 시각 (구매거절일경우)
				}

				//구매확인/거절 처리 성공시(PG사에 요청이 처리된경우)
				if ( $resultCode == "00" ) {
					if ( $iniescrow->GetResult( "CNF_Date" ) != "" ) {
						$order->update_status( 'completed' ); //주문처리완료 상태
						pafw_update_meta_data( $order, '_pafw_escrow_order_confirm', 'yes' );
						pafw_update_meta_data( $order, '_pafw_escrow_order_confirm_time', current_time( 'mysql' ) );
						$order->add_order_note( sprintf( __( '고객님께서 에스크로 구매확인을 <font color=blue><strong>확정</strong></font>하였습니다. 거래번호 : %s, 결과코드 : %s, 처리날짜 : %s, 처리시각 : %s', 'pgall-for-woocommerce' ), $escrow_params['tid'], $resultCode, $resultDate, $resultTime ) );
						wp_send_json_success( __( '에스크로 구매확정 처리가 되었습니다..', 'pgall-for-woocommerce' ) );
					} else {
						$order->update_status( 'cancel-request' );  //주문처리완료 상태로 변경
						pafw_update_meta_data( $order, '_pafw_escrow_order_confirm_reject', 'yes' );
						pafw_update_meta_data( $order, '_pafw_escrow_order_confirm_reject_time', current_time( 'mysql' ) );
						$order->add_order_note( sprintf( __( '고객님께서 에스크로 구매확인을 <font color=red><strong>거절</strong></font>하였습니다. 거래번호 : %s, 결과코드 : %s, 처리날짜 : %s, 처리시각 : %s', 'pgall-for-woocommerce' ), $escrow_params['tid'], $resultCode, $resultDate, $resultTime ) );
						wp_send_json_success( __( '에스크로 구매거절 처리가 되었습니다..', 'pgall-for-woocommerce' ) );
					}

				} else {
					throw new Exception( __( "에러가 발생하였습니다. 다시 한번 더 시도해주시거나, 관리자에게 문의하여 주십시오!", 'pgall-for-woocommerce' ) . " ERROR CODE : " . $resultCode . ", MSG : " . mb_convert_encoding( $resultMsg, "UTF-8", "EUC-KR" ) );
				}
			}
			function escrow_approve_reject() {
				$this->check_shop_order_capability();

				$order = $this->get_order();
				$tid   = $this->get_transaction_id( $order );

				if ( version_compare( PHP_VERSION, '7.1.0' ) < 0 ) {
					require_once( PAFW()->plugin_path() . "/lib/inicis/inipay50/INILib.php" );
				} else {
					require_once( PAFW()->plugin_path() . "/lib/inicis/inipay50_71/INILib.php" );
				}

				$iniescrow = new INIpay50();

				$iniescrow->SetField( "inipayhome", $this->settings['libfolder'] );       // 이니페이 홈디렉터리(상점수정 필요)
				$iniescrow->SetField( "tid", $tid );                                          // 거래아이디
				$iniescrow->SetField( "mid", $this->merchant_id );                                          // 상점아이디
				$iniescrow->SetField( "admin", "1111" );                                               // 키패스워드(상점아이디에 따라 변경)
				$iniescrow->SetField( "type", "escrow" );                                             // 고정 (절대 수정 불가)
				$iniescrow->SetField( "escrowtype", "dcnf" );                                         // 고정 (절대 수정 불가)
				$iniescrow->SetField( "dcnf_name", $this->delivery_register_name );
				$iniescrow->SetField( "debug", "false" );                                               // 로그모드("true"로 설정하면 상세한 로그가 생성됨)

				$iniescrow->startAction();

				$tid        = $iniescrow->GetResult( "tid" );              // 거래번호
				$resultCode = $iniescrow->GetResult( "ResultCode" );       // 결과코드 ("00"이면 지불 성공)
				$resultMsg  = $iniescrow->GetResult( "ResultMsg" );        // 결과내용 (지불결과에 대한 설명)
				$resultDate = $iniescrow->GetResult( "DCNF_Date" );        // 처리 날짜
				$resultTime = $iniescrow->GetResult( "DCNF_Time" );        // 처리 시각

				if ( $resultCode == '00' ) {
					$order->update_status( 'refunded', '관리자에 의해 주문이 취소 되었습니다.' );

					pafw_update_meta_data( $order, '_pafw_order_cancelled', 'yes' );
					pafw_update_meta_data( $order, '_pafw_cancel_date', current_time( 'mysql' ) );

					$order->add_order_note( sprintf( __( '에스크로 구매거절을 %s님께서 <font color=blue><strong>확인</strong></font>하였습니다. 에스크로 환불처리 완료하였습니다. 거래번호 : %s, 결과코드 : %s, 처리날짜 : %s, 처리시각 : %s', 'pgall-for-woocommerce' ), $this->delivery_register_name, $tid, $resultCode, $resultDate, $resultTime ) );
				} else {
					$order->add_order_note( sprintf( __( '에스크로 구매거절을 %s님께서 <font color=blue><strong>확인실패</strong></font>하였습니다. 에스크로 환불처리를 실패하였습니다. 에러메시지를 확인하세요! 거래번호 : %s, 결과코드 : %s, 에러메시지 : %s, 처리날짜 : %s, 처리시각 : %s', 'pgall-for-woocommerce' ), $this->delivery_register_name, $tid, $resultCode, mb_convert_encoding( $resultMsg, "UTF-8", "EUC-KR" ), $resultDate, $resultTime ) );
					throw new Exception( sprintf( __( '구매거절확인 처리중 오류가 발생했습니다. [%s] %s', 'pgall-for-woocommerce' ), $resultCode, mb_convert_encoding( $resultMsg, "UTF-8", "EUC-KR" ) ) );
				}

				wp_send_json_success( __( '구매거절 확인이 정상적으로 처리되었습니다.', 'pgall-for-woocommerce' ) );
			}
			function escrow_register_delivery_info() {
				$this->check_shop_order_capability();

				$order = $this->get_order();
				$escrow_type     = isset( $_REQUEST['escrow_type'] ) ? $_REQUEST['escrow_type'] : '';
				$tracking_number = isset( $_REQUEST['tracking_number'] ) ? $_REQUEST['tracking_number'] : '';

				if ( empty( $tracking_number ) || empty( $escrow_type ) ) {
					throw new Exception( __( '필수 파라미터가 누락되었습니다.', 'pgall-for-woocommerce' ) );
				}

				if ( version_compare( PHP_VERSION, '7.1.0' ) < 0 ) {
					require_once( PAFW()->plugin_path() . "/lib/inicis/inipay50/INILib.php" );
				} else {
					require_once( PAFW()->plugin_path() . "/lib/inicis/inipay50_71/INILib.php" );
				}

				$iniescrow = new INIpay50();
				$iniescrow->SetField( "inipayhome", $this->settings['libfolder'] );       // 이니페이 홈디렉터리(상점수정 필요)
				$iniescrow->SetField( "mid", $this->merchant_id ); // 상점아이디
				$iniescrow->SetField( "tid", $this->get_transaction_id( $order )); // 거래아이디
				$iniescrow->SetField( "oid", pafw_get_meta( $order, '_pafw_txnid' ) ); // 주문번호
				$iniescrow->SetField( "admin", "1111" ); // 키패스워드(상점아이디에 따라 변경)
				$iniescrow->SetField( "type", "escrow" );                                     // 고정 (절대 수정 불가)
				$iniescrow->SetField( "escrowtype", "dlv" );                                  // 고정 (절대 수정 불가)
				$iniescrow->SetField( "dlv_ip", getenv( "REMOTE_ADDR" ) ); // 고정
				$iniescrow->SetField( "debug", "false" ); // 로그모드("true"로 설정하면 상세한 로그가 생성됨)
				$iniescrow->SetField( "soid", "1" );
				$iniescrow->SetField( "dlv_date", '' );
				$iniescrow->SetField( "dlv_time", '' );
				$iniescrow->SetField( "dlv_report", $escrow_type );
				$iniescrow->SetField( "dlv_invoice", $tracking_number );
				$iniescrow->SetField( "dlv_name", $this->delivery_register_name );

				$iniescrow->SetField( "dlv_excode", '9999' );
				$iniescrow->SetField( "dlv_exname", $this->delivery_company_name );  //택배사 이름 (코드가 9999일때, 임의 택배사 이름 입력)
				$iniescrow->SetField( "dlv_charge", 'SH' );  //배송비지급방법(SH:판매자부담, BH:구매자부담)

				$iniescrow->SetField( "dlv_invoiceday", date( "Y-m-d H:i:s" ) );
				$iniescrow->SetField( "dlv_sendname", $this->delivery_register_name );
				$iniescrow->SetField( "dlv_sendpost", $this->delivery_sender_postnum );
				$iniescrow->SetField( "dlv_sendaddr1", $this->delivery_sender_addr1 );
				$iniescrow->SetField( "dlv_sendaddr2", '' );
				$iniescrow->SetField( "dlv_sendtel", $this->delivery_sender_phone );

				if ( ! class_exists( 'MC_MShop' ) ) {
					$recv_postnum = pafw_get_object_property( $order, 'shipping_postcode' );
				} else {
					$recv_postnum = pafw_get_meta( $order, '_mshop_shipping_address-postnum' );
				}
				$recv_addr = pafw_get_object_property( $order, 'shipping_address_1' ) . ' ' . pafw_get_object_property( $order, 'shipping_address_2' );
				$recv_tel  = pafw_get_customer_phone_number( $order );

				$iniescrow->SetField( "dlv_recvname", pafw_get_object_property( $order, 'billing_first_name' ) );
				$iniescrow->SetField( "dlv_recvpost", $recv_postnum );
				$iniescrow->SetField( "dlv_recvaddr", $recv_addr );
				$iniescrow->SetField( "dlv_recvtel", $recv_tel );

				$iniescrow->SetField( "dlv_goodscode", pafw_get_object_property( $order, 'id' ) );
				$iniescrow->SetField( "dlv_goods", $this->make_product_info( $order ) );
				$iniescrow->SetField( "dlv_goodscnt", '' );
				$iniescrow->SetField( "price", floor( $order->get_total() ) );
				$iniescrow->SetField( "dlv_reserved1", '' );
				$iniescrow->SetField( "dlv_reserved2", '' );
				$iniescrow->SetField( "dlv_reserved3", '' );

				$iniescrow->SetField( "pgn", '' );

				$iniescrow->startAction();

				$tid        = $iniescrow->GetResult( "tid" );                    // 거래번호
				$resultCode = $iniescrow->GetResult( "ResultCode" );     // 결과코드 ("00"이면 지불 성공)
				$resultMsg  = $iniescrow->GetResult( "ResultMsg" );          // 결과내용 (지불결과에 대한 설명)
				$dlv_date   = $iniescrow->GetResult( "DLV_Date" );
				$dlv_time   = $iniescrow->GetResult( "DLV_Time" );

				if ( $resultCode == "00" ) {
					pafw_update_meta_data( $order, '_pafw_escrow_tracking_number', $tracking_number );
					pafw_update_meta_data( $order, '_pafw_escrow_register_delivery_info', 'yes' );
					pafw_update_meta_data( $order, '_pafw_escrow_register_delivery_time', current_time( 'mysql' ) );

					$order->add_order_note( __( '판매자님께서 고객님의 에스크로 결제 주문을 배송 등록 또는 수정 처리하였습니다.', 'pgall-for-woocommerce' ), true );
					$order->update_status( $this->order_status_after_enter_shipping_number );
				} else {
					throw new Exception( sprintf( __( '배송등록중 오류가 발생했습니다. [%s] %s', 'pgall-for-woocommerce' ), $resultCode, mb_convert_encoding( $resultMsg, "UTF-8", "EUC-KR" ) ) );
				}

				wp_send_json_success( __( '배송등록이 처리되었습니다.', 'pgall-for-woocommerce' ) );
			}

		}
	}

} // class_exists function end