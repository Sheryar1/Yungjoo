<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Nicepay_Vbank' ) ) {

		class WC_Gateway_Nicepay_Vbank extends WC_Gateway_Nicepay {

			public function __construct() {
				$this->id         = 'nicepay_vbank';
				$this->has_fields = false;

				parent::__construct();

				$this->settings['paymethod'] = 'VBANK';

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '가상계좌 무통장입금', 'pgall-for-woocommerce' );
					$this->description = __( '가상계좌 안내를 통해 무통장입금을 할 수 있습니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}

				$this->success_code = '4100';
				$this->supports[] = 'pafw-vbank';
			}

			public function is_refundable( $order, $screen = 'admin' ) {
				return parent::is_refundable( $order, $screen) && 'yes' != pafw_get_meta( $order, '_pafw_vbank_noti_received' );
			}

			public function get_vbank_list() {
				return array (
					"001" => "한국은행(001)",
					"002" => "산업은행(002)",
					"003" => "기업은행(003)",
					"004" => "국민은행(004)",
					"005" => "외환은행(005)",
					"007" => "수협중앙회(007)",
					"008" => "수출입은행(008)",
					"011" => "농협중앙회(011)",
					"012" => "농협회원조합(012)",
					"020" => "우리은행(020)",
					"023" => "SC제일은행(023)",
					"027" => "한국씨티은행(027)",
					"031" => "대구은행(031)",
					"032" => "부산은행(032)",
					"034" => "광주은행(034)",
					"035" => "제주은행(035)",
					"037" => "전북은행(037)",
					"039" => "경남은행(039)",
					"045" => "새마을금고연합회(045)",
					"048" => "신협중앙회(048)",
					"050" => "상호저축은행(050)",
					"052" => "모건스탠리은행(052)",
					"054" => "HSBC은행(054)",
					"055" => "도이치은행(055)",
					"056" => "에이비엔암로은행(056)",
					"057" => "제이피모간체이스은행(057)",
					"058" => "미즈호코퍼레이트은행(058)",
					"059" => "미쓰비시도쿄UFJ은행(059)",
					"060" => "BOA(060)",
					"071" => "정보통신부 우체국(071)",
					"076" => "신용보증기금(076)",
					"077" => "기술신용보증기금(077)",
					"081" => "하나은행(081)",
					"088" => "신한은행(088)",
					"093" => "한국주택금융공사(093)",
					"094" => "서울보증보험(094)",
					"095" => "경찰청(095)",
					"099" => "금융결제원(099)",
					"209" => "동양종합금융증권(209)",
					"218" => "현대증권(218)",
					"230" => "미래에셋증권(230)",
					"238" => "대우증권(238)",
				);
			}
			public function process_standard( $order, $responseDTO ) {
				$transaction_id = $responseDTO->getParameter( "TID" );
				$txnid          = $responseDTO->getParameter( "Moid" );
				$vacc_num       = $responseDTO->getParameter( "VbankNum" );
				$vacc_bank_code = $responseDTO->getParameterUTF( "VbankBankCode" );
				$vacc_bank_name = mb_convert_encoding( $responseDTO->getParameter( "VbankBankName" ), "UTF-8", "CP949" );
				$vacc_depositor = mb_convert_encoding( $responseDTO->getParameter( "BuyerName" ), "UTF-8", "CP949" );
				$vacc_date      = $responseDTO->getParameter( "VbankExpDate" );

				pafw_update_meta_data( $order, '_pafw_vacc_tid', $transaction_id );
				pafw_update_meta_data( $order, '_pafw_vacc_num', trim( $vacc_num ) );  //입금계좌번호
				pafw_update_meta_data( $order, '_pafw_vacc_bank_code', trim( $vacc_bank_code ) );    //입금은행코드
				pafw_update_meta_data( $order, '_pafw_vacc_bank_name', $vacc_bank_name );    //입금은행명/코드
				pafw_update_meta_data( $order, '_pafw_vacc_holder', '' );    //예금주
				pafw_update_meta_data( $order, '_pafw_vacc_depositor', $vacc_depositor );   //송금자
				pafw_update_meta_data( $order, '_pafw_vacc_date', trim( $vacc_date ) );    //입금예정일

				$this->add_payment_log( $order, '[ 가상계좌 입금 대기중 ]', array (
					'거래번호' => $transaction_id
				) );

				pafw_reduce_order_stock( $order );

				$order->update_status( $this->settings['order_status_after_vbank_payment'] );

				//WC 3.0 postmeta update 로 인해 별도로 가상계좌 추가 처리
				if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
					$order->set_date_paid( null );
					$order->save();
				}
			}
			function process_vbank_notification( $posted = null ) {
				$order = null;

				try {

					$this->add_log( '가상계좌 입금통보 시작 : ' . $_SERVER['REMOTE_ADDR'] );

					@extract( $_GET );
					@extract( $_POST );
					@extract( $_SERVER );

					$PayMethod      = $PayMethod;                //지불수단
					$M_ID           = $MID;                            //상점ID
					$MallUserID     = $MallUserID;                //회원사 ID
					$Amt            = $Amt;                            //금액
					$name           = $name;                        //구매자명
					$GoodsName      = $GoodsName;                //상품명
					$TID            = $TID;                            //거래번호
					$MOID           = $MOID;                            //주문번호
					$AuthDate       = $AuthDate;                    //입금일시 (yyMMddHHmmss)
					$ResultCode     = $ResultCode;                //결과코드 ('4110' 경우 입금통보)
					$ResultMsg      = $ResultMsg;                //결과메시지
					$VbankNum       = $VbankNum;                    //가상계좌번호
					$FnCd           = $FnCd;                            //가상계좌 은행코드
					$VbankName      = $VbankName;                //가상계좌 은행명
					$VbankInputName = $VbankInputName;        //입금자 명

					//가상계좌채번시 현금영수증 자동발급신청이 되었을경우 전달되며
					//RcptTID 에 값이 있는경우만 발급처리 됨
					$RcptTID      = $RcptTID;                    //현금영수증 거래번호
					$RcptType     = trim( $RcptType );            //현금 영수증 구분(0:미발행, 1:소득공제용, 2:지출증빙용)
					$RcptAuthCode = $RcptAuthCode;            //현금영수증 승인번호

					$RcptTypeMsg = '';
					switch ( $RcptType ) {
						case '0':
							$RcptTypeMsg = '미발행';
							break;
						case '1':
							$RcptTypeMsg = '소득공제용';
							break;
						case '2':
							$RcptTypeMsg = '지출증빙용';
							break;
						default:
							$RcptTypeMsg = '미발행';
					}

					//PG사에서 전달한 IP 인지 확인
					$PG_IP = $_SERVER['REMOTE_ADDR'];

					//디버그 모드인경우
					if ( WP_DEBUG == 'true' ) {
						$debug_ipaddr = ! empty( $this->settings['debug_ip'] ) ? $this->settings['debug_ip'] : '';
					}

					//결제결과에 따른 처리 진행
					$orderid = explode( '_', $MOID );
					$orderid = (int) $orderid[0];
					$order   = wc_get_order( $orderid );

					if ( in_array( $PG_IP, array ( '121.133.126.10', '121.133.126.11','211.33.136.39', $debug_ipaddr ) ) ) {

						//가상계좌 입금통보인지 결과 코드 확인
						if ( trim( $ResultCode ) == '4110' && trim( $PayMethod ) == 'VBANK' ) {

							if ( ! in_array( $order->get_status(), array ( 'completed', 'cancelled', 'refunded' ) ) ) {  //주문상태 확인

								//가상계좌 정보 로딩처리
								$nicepay_vbank_info = get_post_meta( pafw_get_object_property( $orderid, 'id' ), '_nicepay_vbank_info', true );
								$nicepay_vbank_info = json_decode( $nicepay_vbank_info, JSON_UNESCAPED_UNICODE );

								//주문에 저장된 TID값 가져오기
								$order_tid = $this->get_transaction_id( $order );

								if ( trim( $TID ) != $order_tid ) {
									throw new Exception( 'TID 불일치' );
								}

								if ( trim( $Amt ) != (int) $order->get_total() ) {    //입금액 체크
									throw new Exception( '입금액 불일치' );
								}

								if ( trim( $VbankNum ) != pafw_get_meta( $order, '_pafw_vacc_num' ) ) {    //가상계좌 계좌번호 체크
									throw new Exception( '가상계좌번호 불일치' );
								}

								if ( trim( $FnCd ) != pafw_get_meta( $order, '_pafw_vacc_bank_code' ) ) {    //가상계좌 은행코드 체크
									throw new Exception( '은행코드 불일치' );
								}

								pafw_update_meta_data( $order, '_pafw_vbank_noti_received', 'yes' );
								pafw_update_meta_data( $order, '_pafw_vbank_noti_transaction_date', '20' . $_REQUEST['AuthDate'] );
								pafw_update_meta_data( $order, '_pafw_vbank_noti_deposit_bank', mb_convert_encoding( $_REQUEST['VbankName'], "UTF-8", "CP949" ) );
								pafw_update_meta_data( $order, '_pafw_vbank_noti_depositor', mb_convert_encoding( $_REQUEST['VbankInputName'], "UTF-8", "CP949" ) );
								pafw_update_meta_data( $order, '_pafw_cash_receipts', $RcptType );

								$this->add_payment_log( $order, '[ 가상계좌 입금완료 ]', array (
									'입금시각' => $AuthDate
								) );

								//주문 완료 처리
								$order->payment_complete( $order_tid );

								do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );

								//주문 상태 변경 처리
								$order->update_status( $this->settings['order_status_after_payment'] );

								//WC3.0 관련 가상계좌 입금통보시 결제 완료 시간 갱신 처리
								if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
									$order->set_date_paid( current_time( 'timestamp', true ) );
									$order->save();
								}
								echo "OK";      //성공

							} else {
								throw new Exception( sprintf( '잘못된 요청입니다. 결과코드 : %s, 결제수단 : %s', $ResultCode, $PayMethod ) );
							}
						} else {
							throw new Exception( sprintf( '주문상태(%s)가 올바르지 않습니다.', wc_get_order_status_name( $order->get_status() ) ) );
						}

					} else {
						throw new Exception( sprintf( '비정상 접근입니다. [ %s ]', $PG_IP ) );
					}
				} catch ( Exception $e ) {
					$this->add_log( "[오류] " . $e->getMessage() . "\n" . print_r( $_REQUEST, true ) );

					if ( $order ) {
						$this->add_payment_log( $order, '[ 가상계좌 입금오류 ]', $e->getMessage(), false );
					}
					echo "FAIL";
					exit();
				}
			}
			function vbank_refund_request() {
				$this->check_shop_order_capability();

				$order = $this->get_order();

				$vbank_list = $this->get_vbank_list();
				pafw_update_meta_data( $order, '_pafw_vbank_refund_bank_code', $_REQUEST['refund_bank_code'] );
				pafw_update_meta_data( $order, '_pafw_vbank_refund_bank_name', $vbank_list[ $_REQUEST['refund_bank_code'] ] );
				pafw_update_meta_data( $order, '_pafw_vbank_refund_acc_num', $_REQUEST['refund_acc_num'] );
				pafw_update_meta_data( $order, '_pafw_vbank_refund_acc_name', $_REQUEST['refund_acc_name'] );
				pafw_update_meta_data( $order, '_pafw_vbank_refund_reason', $_REQUEST['refund_reason'] );
				pafw_update_meta_data( $order, '_pafw_vbank_refunded', 'yes' );
				pafw_update_meta_data( $order, '_pafw_order_cancelled', 'yes' );
				pafw_update_meta_data( $order, '_pafw_cancel_date', current_time( 'mysql' ) );

				$order->update_status( 'refunded' );
				$order->add_order_note( __( '환불계좌 등록이 완료되었습니다. 환불처리는 해당 계좌로 직접 이체해 주셔야 합니다.', 'pgall-for-woocommerce' ) );
				wp_send_json_success( __( '환불계좌 등록이 완료되었습니다. 환불처리는 해당 계좌로 직접 이체해 주셔야 합니다.', 'pgall-for-woocommerce' ) );
			}
		}
	}

} // class_exists function end