<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Inicis_Stdcard' ) ) :

	class WC_Gateway_Inicis_Stdcard extends WC_Gateway_Inicis {

		protected $repay_supported_cards = array (
			'11' => '00',   //BC
			'06' => '04',   //국민,국민은행
			'12' => '00',   //삼성
			'14' => '26',   //신한,신한은행
			'01' => '05',   //외환,외환은행
			'04' => '00',   //현대 (부분취소 횟수제한없음)
			'03' => '00',   //롯데 (부분취소 횟수제한없음)
			'17' => '81',   //하나SK,하나은행
			'16' => '11',   //NH카드,농협
			'26' => '00',   //은련
		);

		//부분 취소 횟수 제한없는 카드사 정보 리스트 설정
		protected $repay_count_limits = array (
			'04' => '00',   //현대 (부분취소 횟수제한없음)
			'03' => '00',   //롯데 (부분취소 횟수제한없음)
		);

		public function __construct() {
			$this->id = 'inicis_stdcard';

			parent::__construct();

			$this->settings['gopaymethod'] = 'card';
			$this->settings['paymethod']   = 'wcard';

			if ( empty( $this->settings['title'] ) ) {
				$this->title       = __( '신용카드 결제', 'pgall-for-woocommerce' );
				$this->description = __( '구글크롬, IE, Safari 에서 결제 가능한 웹표준 결제 입니다 결제를 진행해 주세요.', 'pgall-for-woocommerce' );
			} else {
				$this->title       = $this->settings['title'];
				$this->description = $this->settings['description'];
			}

			$this->supports[] = 'refunds';
		}
		function process_standard( $order, $result_map ) {
			//카드관련 추가정보 추가
			pafw_update_meta_data( $order, "_pafw_card_num", $result_map['CARD_Num'] );          //카드번호
			pafw_update_meta_data( $order, "_pafw_card_code", $result_map['CARD_Code'] );        //신용카드사 코드
			pafw_update_meta_data( $order, "_pafw_card_bank_code", $result_map['CARD_BankCode'] );        //신용카드 발급사 코드
			pafw_update_meta_data( $order, "_pafw_card_name", $this->get_card_name( $result_map['CARD_Code'] ) );    //신용카드사명

			$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
				'이니시스 거래번호' => $result_map['tid'],
				'몰 고유 주문번호' => $result_map['MOID']
			) );
		}
		function process_mobile_next( $order, $inimx ) {
			pafw_update_meta_data( $order, '_pafw_card_num', $inimx->m_cardNumber );          //카드번호
			pafw_update_meta_data( $order, '_pafw_card_qouta', $inimx->m_cardQuota );      //할부기간
			pafw_update_meta_data( $order, '_pafw_card_code', $inimx->m_cardCode );        //신용카드사 코드
			pafw_update_meta_data( $order, '_pafw_card_bank_code', $inimx->m_cardIssuerCode );        //신용카드 발급사 코드
			pafw_update_meta_data( $order, '_pafw_card_name', $this->get_card_name( $inimx->m_cardCode ) );    //신용카드사명

			$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
				'이니시스 거래번호' => $inimx->m_tid,
				'몰 고유 주문번호' => $inimx->m_moid
			) );
		}
		function can_repay( $order, $card_code, $card_bank_code, $repay_count ) {
			if ( ! $this->is_refundable( $order ) ) {
				return new WP_Error( 'ERR-PAFW-PR', '부분취소 불가능 주문상태' );
			}

			if ( ! isset( $this->repay_supported_cards[ $card_code ] ) || $this->repay_supported_cards[ $card_code ] != $card_bank_code ) {
				return new WP_Error( 'ERR-PAFW-PR', '카드사 미지원' );
			};

			if ( isset( $this->repay_count_limits[ $card_code ] ) && $this->repay_count_limits[ $card_code ] == $card_bank_code && $repay_count > 100 ) {
				return new WP_Error( 'ERR-PAFW-PR', '최대 취소 횟수 초과' );
			}

			return true;
		}

		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			$order = wc_get_order( $order_id );

			if ( $amount == $order->get_total() ) {
				if ( ! $this->is_refundable( $order ) ) {
					throw new Exception( __( '주문을 취소할 수 없는 상태입니다.', 'pgall-for-woocommerce' ) );
				}

				$transaction_id = $this->get_transaction_id( $order );

				if ( empty( $transaction_id ) ) {
					throw new Exception( __( '주문 정보에 오류가 있습니다.', 'pgall-for-woocommerce' ) );
				}

				$response = $this->cancel_request( $order, __( '관리자 주문취소', 'pgall-for-woocommerce' ), __( 'CM_CANCEL_002', 'pgall-for-woocommerce' ) );

				if ( $response == "success" ) {
					$order->update_status( 'refunded', '관리자에 의해 주문이 취소 되었습니다.' );
					$this->add_payment_log( $order, '[ 결제 취소 완료 ]', '관리자에 의해 주문이 취소 되었습니다.' );
					pafw_update_meta_data( $order, '_pafw_order_cancelled', 'yes' );
					pafw_update_meta_data( $order, '_pafw_cancel_date', current_time( 'mysql' ) );
				} else {
					throw new Exception( __( "주문 취소 시도중 오류가 발생했습니다.\r\n\r\n내용 : ", 'pgall-for-woocommerce' ) . $response );
				}

				return true;
			} else {
				$this->do_repay( $order, intval( $amount ), true );

				return true;
			}
		}
		function do_repay( $order, $amount, $already_refunded = false ) {

			if ( version_compare( PHP_VERSION, '7.1.0' ) < 0 ) {
				require_once( PAFW()->plugin_path() . "/lib/inicis/inipay50/INILib.php" );
			} else {
				require_once( PAFW()->plugin_path() . "/lib/inicis/inipay50_71/INILib.php" );
			}

			$inipay = new INIpay50();

			$order_id       = pafw_get_object_property( $order, 'id' );
			$transaction_id = $this->get_transaction_id( $order );

			$confirm_price = ( ( $order->get_total() - $order->get_total_refunded() ) - $amount );    //재승인 요청금액(기존승인금액 - 취소금액)
			if ( $already_refunded ) {
				$confirm_price += $amount;
			}

			$tax     = ( $amount * 1.1 );
			$taxfree = 0;

			$inipay->SetField( "inipayhome", $this->settings['libfolder'] );
			$inipay->SetField( "type", "repay" );      // 고정 (절대 수정 불가)
			$inipay->SetField( "pgid", "INIphpRPAY" );      // 고정 (절대 수정 불가)
			$inipay->SetField( "subpgip", "203.238.3.10" );                // 고정
			$inipay->SetField( "debug", "false" );        // 로그모드("true"로 설정하면 상세로그가 생성됨.)
			$inipay->SetField( "mid", $this->settings['merchant_id'] );
			$inipay->SetField( "admin", "1111" );         //비대칭 사용키 키패스워드
			$inipay->SetField( "oldtid", $transaction_id );            // 취소할 거래의 거래아이디
			$inipay->SetField( "currency", 'WON' );     // 화폐단위
			$inipay->SetField( "price", $amount );      //취소금액
			$inipay->SetField( "confirm_price", $confirm_price );      //승인요청금액
			$inipay->SetField( "buyeremail", pafw_get_object_property( $order, 'billing_email' ) );      // 구매자 이메일 주소
			$inipay->SetField( "tax", $tax );
			$inipay->SetField( "taxfree", $taxfree );

			$inipay->startAction();

			if ( $inipay->getResult( 'ResultCode' ) == "00" ) {

				do_action( 'pafw_payment_action', 'cancelled', $amount, $order, $this );

				$refund_reason = __( '관리자의 요청에 의한 부분취소', 'pgall-for-woocommerce' );

				if ( ! $already_refunded ) {
					//부분 환불 처리
					$refund = wc_create_refund( array (
						'amount'     => $amount,
						'reason'     => $refund_reason,
						'order_id'   => $order_id,
						'line_items' => array (),
					) );
				}

				//부분취소후 재승인 금액이 0원인 경우 모든 금액을 부분환불 처리한 것으로 이경우 환불됨 상태로 변경처리.
				if ( $confirm_price == 0 ) {
					$order->update_status( 'refunded' );
					pafw_update_meta_data( $order, '_pafw_order_cancelled', 'yes' );
					pafw_update_meta_data( $order, '_pafw_cancel_date', current_time( 'mysql' ) );
				}

				//부분환불 정보 확인
				$inicis_repay = pafw_get_meta( $order, '_pafw_repay' );
				$inicis_repay = json_decode( $inicis_repay, true );

				if ( ! empty( $inicis_repay ) ) {
					//부분환불 정보가 있음. 기존 정보에 추가하여 처리
					$repay_cnt                          = count( $inicis_repay );
					$inicis_repay[ ( $repay_cnt + 1 ) ] = array (
						'newtid'       => $inipay->getResult( 'TID' ),                      //신거래번호TID
						'oldtid'       => $inipay->getResult( 'PRTC_TID' ),                 //원거래번호TID
						'result_code'  => $inipay->getResult( 'ResultCode' ),          //결과코드
						'result_msg'   => mb_convert_encoding( $inipay->GetResult( 'ResultMsg' ), "UTF-8", "EUC-KR" ),            //결과메시지
						'refund_price' => $inipay->getResult( 'PRTC_Price' ),        //부분취소요청금액
						'remain_price' => $inipay->getResult( 'PRTC_Remains' ),      //최종결제금액(부분취소후 남은결제금액)
						'type'         => $inipay->getResult( 'PRTC_Type' ),                 //부분취소, 재승인 구분값(0:재승인,1:부분취소)
						'req_cnt'      => $inipay->getResult( 'PRTC_Cnt' ),              //부분취소(재승인)요청횟수
					);
				} else {
					//부분환불 정보가 확인안되는 경우 최초 등록시로 처리
					$inicis_repay['1'] = array (
						'newtid'       => $inipay->getResult( 'TID' ),                      //신거래번호TID
						'oldtid'       => $inipay->getResult( 'PRTC_TID' ),                 //원거래번호TID
						'result_code'  => $inipay->getResult( 'ResultCode' ),          //결과코드
						'result_msg'   => mb_convert_encoding( $inipay->GetResult( 'ResultMsg' ), "UTF-8", "EUC-KR" ),            //결과메시지
						'refund_price' => $inipay->getResult( 'PRTC_Price' ),        //부분취소요청금액
						'remain_price' => $inipay->getResult( 'PRTC_Remains' ),      //최종결제금액(부분취소후 남은결제금액)
						'type'         => $inipay->getResult( 'PRTC_Type' ),                 //부분취소, 재승인 구분값(0:재승인,1:부분취소)
						'req_cnt'      => $inipay->getResult( 'PRTC_Cnt' ),              //부분취소(재승인)요청횟수
					);
				}

				$this->add_payment_log( $order, '[ 부분 취소 성공 ]', array (
					'이니시스 거래번호' => $inipay->getResult( 'TID' ),
					'부분취소 금액'   => $inipay->getResult( 'PRTC_Price' )
				) );

				pafw_update_meta_data( $order, '_pafw_repay', json_encode( $inicis_repay, JSON_UNESCAPED_UNICODE ) );
			} else {
				$this->add_payment_log( $order, '[ 부분 취소 실패 ]', mb_convert_encoding( $inipay->GetResult( 'ResultMsg' ), "UTF-8", "EUC-KR" ), false );

				throw new Exception( mb_convert_encoding( $inipay->GetResult( 'ResultMsg' ), "UTF-8", "EUC-KR" ) );
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

			if ( $this->is_refundable( $order ) && ! in_array( $order->get_status(), apply_filters( 'woocommerce_valid_order_statuses_for_payment', array ( 'on-hold', 'pending', 'failed' ), $order ) ) ) {
				add_meta_box(
					'pafw-order-repay',
					__( '신용카드 부분환불 <span class="pafw-powerd"><a target="_blank" href="https://www.codemshop.com/">Powered by CodeM</a></span>', 'pgall-for-woocommerce' ),
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
	}

endif;