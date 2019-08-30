<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Kcp_VBank' ) ) :

	class WC_Gateway_Kcp_VBank extends WC_Gateway_Kcp {

		public function __construct() {
			$this->id = 'kcp_vbank';

			parent::__construct();

			$this->settings['pc_paymethod']     = '001000000000';
			$this->settings['mobile_paymethod'] = 'vcnt';
			$this->settings['bills_cmd']        = 'vcnt_bill';

			if ( empty( $this->settings['title'] ) ) {
				$this->title       = __( '가상계좌', 'pgall-for-woocommerce' );
				$this->description = __( '가상계좌 안내를 통해 무통장입금을 할 수 있습니다.', 'pgall-for-woocommerce' );
			} else {
				$this->title       = $this->settings['title'];
				$this->description = $this->settings['description'];
			}
			$this->supports[] = 'pafw-vbank';
		}

		public function get_vbank_list() {
			return array (
				"39" => "경남은행",
				"45" => "새마을금고",
				"35" => "제주은행",
				"34" => "광주은행",
				"07" => "수협",
				"81" => "하나은행",
				"04" => "국민은행",
				"88" => "신한은행",
				"27" => "한국씨티은행",
				"03" => "기업은행",
				"48" => "신협",
				"54" => "HSBC",
				"11" => "농협",
				"05" => "외환은행",
				"23" => "SC은행",
				"31" => "대구은행",
				"20" => "우리은행",
				"02" => "산업은행",
				"32" => "부산은행",
				"71" => "우체국",
				"37" => "전북은행",
				"64" => "산림조합"
			);
		}
		public function process_payment_result( $order, $c_PayPlus ) {
			$transaction_id = $c_PayPlus->mf_get_res_data( "tno" );
			$bank_name      = iconv( 'euc-kr', 'UTF-8', $c_PayPlus->mf_get_res_data( "bankname" ) ); // 입금할 은행 이름
			$depositor      = iconv( 'euc-kr', 'UTF-8', $c_PayPlus->mf_get_res_data( "depositor" ) ); // 입금할 계좌 예금주
			$account        = $c_PayPlus->mf_get_res_data( "account" ); // 입금할 계좌 번호
			$va_name        = iconv( 'euc-kr', 'UTF-8', $c_PayPlus->mf_get_res_data( "va_name" ) ); // 가상계좌 입금마감시간
			$va_date        = $c_PayPlus->mf_get_res_data( "va_date" ); // 가상계좌 입금마감시간
			$cash_authno    = $c_PayPlus->mf_get_res_data( "cash_authno" );

			pafw_update_meta_data( $order, '_pafw_vacc_tid', $transaction_id );
			pafw_update_meta_data( $order, '_pafw_vacc_num', $account );
			pafw_update_meta_data( $order, '_pafw_vacc_bank_code', '00' );
			pafw_update_meta_data( $order, '_pafw_vacc_bank_name', $bank_name );    //입금은행명/코드
			pafw_update_meta_data( $order, '_pafw_vacc_holder', $depositor );    //예금주
			pafw_update_meta_data( $order, '_pafw_vacc_depositor', $va_name );   //송금자
			pafw_update_meta_data( $order, '_pafw_vacc_date', $va_date );    //입금예정일
			pafw_update_meta_data( $order, '_pafw_cash_receipts', ! empty( $cash_authno ) ? '발행' : '미발행' );

			$this->add_payment_log( $order, '[ 가상계좌 입금 대기중 ]', array (
				'거래번호' => $transaction_id
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
		public function process_common_return() {
			try {
				$site_cd  = $_POST ["site_cd"];                 // 사이트 코드
				$tno      = $_POST ["tno"];                 // KCP 거래번호
				$order_no = $_POST ["order_no"];                 // 주문번호

				$order = wc_get_order( $order_no );

				// Validate Request
				if ( $site_cd !== $this->kcpfw_option( 'site_cd' ) ) {
					throw new Exception( __( '사이트 코드 불일치', 'pgall-for-woocommerce' ), '7000001' );
				} else if ( empty( $order ) || $tno != $this->get_transaction_id( $order ) ) {
					throw new Exception( sprintf( __( '주문 정보 오류 ( %s, %s, %s )', 'pgall-for-woocommerce' ), $order_no, $tno, $this->get_transaction_id( $order ) ), '7000002' );
				} else {
					$tx_cd = $_POST ["tx_cd"];
					switch ( $tx_cd ) {
						case self::TX_VACC_DEPOSIT :
							$this->process_vbank_notification( $order );
							break;
						case self::TX_ESCROW_DELIVERY :
						case self::TX_ESCROW_CONFIRM :
						case self::TX_ESCROW_CANCEL_IMMEDIATELY :
						case self::TX_ESCROW_CANCEL :
						case self::TX_ESCROW_WITHHOLD_SETTLEMENT :
							$this->process_escrow_notification( $order );
							break;
						default:
							throw new Exception( __( '유효하지 않은 TX_CD', 'pgall-for-woocommerce' ) );
					}
				}
			} catch ( Exception $e ) {
				$message = sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() );
				$this->add_log( $message );
			}

			$this->send_common_return_response();
		}
		protected function process_escrow_notification( $order ) {
			$this->add_log( 'process_escrow_notification' );
			$tx_cd = $_POST ["tx_cd"];                 // 업무처리 구분 코드
			$tx_tm = $_POST ["tx_tm"];                 // 업무처리 완료 시간

			switch ( $tx_cd ) {
				case self::TX_ESCROW_DELIVERY :
					// TO-DO
					break;
				case self::TX_ESCROW_CONFIRM :
					if ( 'Y' == $_POST["st_cd"] ) {
						$order->update_status( 'completed' ); //주문처리완료 상태
						pafw_update_meta_data( $order, '_pafw_escrow_order_confirm', 'yes' );
						pafw_update_meta_data( $order, '_pafw_escrow_order_confirm_time', current_time( 'mysql' ) );

						$this->add_payment_log( $order, '[ 에스크로 구매확정 ]', array (
							'처리시각' => $tx_tm
						) );
					} else {
						$cancel_message = iconv( 'euc-kr', 'UTF-8', $_POST["can_msg"] );
						$order->update_status( 'cancel-request' );  //주문처리완료 상태로 변경
						pafw_update_meta_data( $order, '_pafw_escrow_order_confirm_reject', 'yes' );
						pafw_update_meta_data( $order, '_pafw_escrow_order_confirm_reject_time', current_time( 'mysql' ) );
						pafw_update_meta_data( $order, '_pafw_escrow_order_confirm_reject_message', $cancel_message );

						$this->add_payment_log( $order, '[ 에스크로 구매거절 ]', array (
							'처리시각' => $tx_tm,
							'취소사유' => $cancel_message
						), false );
					}
					break;
			}
		}
		protected function process_vbank_notification( $order ) {
			$this->add_log( 'process_vbank_notification' );

			$site_cd  = $_POST ["site_cd"];                 // 사이트 코드
			$tno      = $_POST ["tno"];                 // KCP 거래번호
			$order_no = $_POST ["order_no"];                 // 주문번호
			$tx_cd    = $_POST ["tx_cd"];                 // 업무처리 구분 코드
			$tx_tm    = $_POST ["tx_tm"];                 // 업무처리 완료 시간
			$ipgm_name = $_POST["ipgm_name"];                // 주문자명
			$remitter  = $_POST["remitter"];                // 입금자명
			$ipgm_mnyx = $_POST["ipgm_mnyx"];                // 입금 금액
			$bank_code = $_POST["bank_code"];                // 은행코드
			$account   = $_POST["account"];                // 가상계좌 입금계좌번호
			$op_cd     = $_POST["op_cd"];                    // 처리구분 코드
			$noti_id   = $_POST["noti_id"];                // 통보 아이디
			$cash_a_no = $_POST["cash_a_no"];                // 현금영수증 승인번호
			$cash_a_dt = $_POST["cash_a_dt"];                // 현금영수증 승인시간

			$wc_tno     = $this->get_transaction_id( $order );
			$wc_account = get_post_meta( $order_no, '_pafw_vacc_num', true );

			if ( $account != $wc_account ) {
				throw new Exception( __( '입금 계좌정보 불일치', 'pgall-for-woocommerce' ), '7000004' );
			} else if ( 'on-hold' != $order->get_status() ) {
				throw new Exception( __( '유효하지 않은 주문상태', 'pgall-for-woocommerce' ), '7000004' );
			} else if ( floatval( $ipgm_mnyx ) != $order->get_total() ) {
				throw new Exception( sprintf( __( '입금금액 불일치 : %s, %s', 'pgall-for-woocommerce' ), $ipgm_mnyx, $order->get_total() ), '7000005' );
			} else {
				$vbank_list = $this->get_vbank_list();

				pafw_update_meta_data( $order, '_pafw_vbank_noti_received', 'yes' );
				pafw_update_meta_data( $order, '_pafw_vbank_noti_transaction_date', $tx_tm );
				pafw_update_meta_data( $order, '_pafw_vbank_noti_deposit_bank', isset( $vbank_list[ $bank_code ] ) ? $vbank_list[ $bank_code ] : $bank_code );
				pafw_update_meta_data( $order, '_pafw_vbank_noti_depositor', $remitter );

				$message = '';
				if ( 'sandbox' === $this->settings['operation_mode'] ) {
					$message .= '[개발모드] ';
				}

				$this->add_payment_log( $order, '[ 가상계좌 입금완료 ]', array (
					'입금시각'  => $tx_tm,
					'통보아이디' => $noti_id
				) );

				$order->payment_complete( $tno );
				$order->update_status( $this->settings['order_status_after_payment'] );

				//WC3.0 관련 가상계좌 입금통보시 결제 완료 시간 갱신 처리
				if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
					$order->set_date_paid( current_time( 'timestamp', true ) );
					$order->save();
				}

				do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );
			}
		}
	}

endif;