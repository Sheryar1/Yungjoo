<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Kcp_Escrow_Bank' ) ) :

	class WC_Gateway_Kcp_Escrow_Bank extends WC_Gateway_Kcp {

		const ESCROW_TYPE_DELIVERY              = 'STE1';
		const ESCROW_TYPE_CANCEL_IMMEDIATELY    = 'STE2';
		const ESCROW_TYPE_WITHHOLD_SETTLEMENT   = 'STE3';
		const ESCROW_TYPE_CANCEL_AFTER_DELIVERY = 'STE4';

		public function __construct() {
			$this->id = 'kcp_escrow_bank';

			$this->is_escrow = true;

			parent::__construct();

			$this->settings['pc_paymethod']     = '010000000000';
			$this->settings['mobile_paymethod'] = 'acnt';
			$this->settings['bills_cmd']        = 'acnt_bill';

			if ( empty( $this->settings['title'] ) ) {
				$this->title       = __( '에스크로 계좌이체', 'pgall-for-woocommerce' );
				$this->description = __( '에스크로 계좌이체를 통해 결제를 할 수 있습니다.', 'pgall-for-woocommerce' );
			} else {
				$this->title       = $this->settings['title'];
				$this->description = $this->settings['description'];
			}
			$this->supports[] = 'pafw-escrow';
		}

		public function is_refundable( $order, $screen = 'admin' ) {
			return ! in_array( $order->get_status(), array( 'completed', 'cancelled', 'refunded' ) ) && 'yes' != pafw_get_meta( $order, '_pafw_escrow_register_delivery_info' );
		}
		public function process_payment_result( $order, $c_PayPlus ) {
			$transaction_id = $c_PayPlus->mf_get_res_data( "tno" );
			$bank_code      = $c_PayPlus->mf_get_res_data( "bank_code" ); // 카드사 코드
			$bank_name      = iconv( 'euc-kr', 'UTF-8', $c_PayPlus->mf_get_res_data( "bank_name" ) );
			$cash_authno    = $c_PayPlus->mf_get_res_data( "cash_authno" );

			pafw_update_meta_data( $order, '_pafw_bank_code', $bank_code );
			pafw_update_meta_data( $order, '_pafw_bank_name', $bank_name );
			pafw_update_meta_data( $order, '_pafw_cash_receipts', ! empty( $cash_authno ) ? '발행' : '미발행' );

			$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
				'거래번호' => $transaction_id
			) );
		}
		function escrow_register_delivery_info() {
			$this->check_shop_order_capability();

			$order = $this->get_order();

			$tracking_number = isset( $_REQUEST['tracking_number'] ) ? $_REQUEST['tracking_number'] : '';

			if ( empty( $tracking_number ) ) {
				throw new Exception( __( '필수 파라미터가 누락되었습니다.', 'pgall-for-woocommerce' ) );
			}

			require_once $this->home_dir() . '/pp_cli_hub_lib.php';

			$cust_ip = getenv( "REMOTE_ADDR" );
			$tran_cd = "00200000";

			$c_PayPlus = new C_PP_CLI;
			$c_PayPlus->mf_clear();

			$c_PayPlus->mf_set_modx_data( "tno", $this->get_transaction_id( $order ) );
			$c_PayPlus->mf_set_modx_data( "mod_ip", $cust_ip );      // 변경 요청자 IP
			$c_PayPlus->mf_set_modx_data( "mod_desc", '' );      // 변경 사유

			$c_PayPlus->mf_set_modx_data( "mod_type", self::ESCROW_TYPE_DELIVERY );      // 원거래 변경 요청 종류
			$c_PayPlus->mf_set_modx_data( "deli_numb", $tracking_number );      // 운송장 번호
			$c_PayPlus->mf_set_modx_data( "deli_corp", $this->settings['delivery_company_name'] );      // 택배 업체명

			$c_PayPlus->mf_do_tx(
				"",
				$this->home_dir(),
				$this->kcpfw_option( 'site_cd' ),
				$this->kcpfw_option( 'site_key' ),
				$tran_cd,
				"",
				$this->kcpfw_option( 'gw_url' ),
				$this->kcpfw_option( 'gw_port' ),
				"payplus_cli_slib",
				"",
				$cust_ip,
				$this->kcpfw_option( 'log_level' ),
				0,
				0,
				$this->log_path()
			); // 응답 전문 처리

			$res_cd  = $c_PayPlus->m_res_cd;  // 결과 코드
			$res_msg = iconv( 'euc-kr', 'UTF-8', $c_PayPlus->m_res_msg ); // 결과 메시지

			if ( $res_cd == "0000" ) {
				pafw_update_meta_data( $order, '_pafw_escrow_tracking_number', $tracking_number );
				pafw_update_meta_data( $order, '_pafw_escrow_register_delivery_info', 'yes' );
				pafw_update_meta_data( $order, '_pafw_escrow_register_delivery_time', current_time( 'mysql' ) );

				$order->add_order_note( __( '판매자님께서 고객님의 에스크로 결제 주문을 배송 등록 또는 수정 처리하였습니다.', 'pgall-for-woocommerce' ), true );
				$order->update_status( $this->order_status_after_enter_shipping_number );
			} else {
				throw new Exception( sprintf( __( '배송등록중 오류가 발생했습니다. [%s] %s', 'pgall-for-woocommerce' ), $res_cd, $res_msg ) );
			}

			wp_send_json_success( __( '배송등록이 처리되었습니다.', 'pgall-for-woocommerce' ) );
		}

		function escrow_approve_reject() {
			$this->cancel_request( null, '' );
		}
		function cancel_request( $order, $msg, $code = "1" ) {
			$this->check_shop_order_capability();

			$order = $this->get_order();

			require_once $this->home_dir() . '/pp_cli_hub_lib.php';

			$cust_ip = getenv( "REMOTE_ADDR" );
			$tran_cd = "00200000";

			$c_PayPlus = new C_PP_CLI;
			$c_PayPlus->mf_clear();

			$c_PayPlus->mf_set_modx_data( "tno", $this->get_transaction_id( $order ) );
			$c_PayPlus->mf_set_modx_data( "mod_ip", $cust_ip );      // 변경 요청자 IP
			$c_PayPlus->mf_set_modx_data( "mod_desc", '' );      // 변경 사유

			if ( 'yes' != pafw_get_meta( $order, '_pafw_escrow_register_delivery_info' ) ) {
				$c_PayPlus->mf_set_modx_data( "mod_desc", '배송 전 취소' );      // 변경 사유
				$mod_type = self::ESCROW_TYPE_CANCEL_IMMEDIATELY;
			} else {
				if ( 'yes' != pafw_get_meta( $order, '_pafw_escrow_order_confirm_reject' ) ) {
					$c_PayPlus->mf_set_modx_data( "mod_desc", '배송 후 취소' );      // 변경 사유
				} else {
					$c_PayPlus->mf_set_modx_data( "mod_desc", '배송 후 고객 요청으로 인한 취소' );      // 변경 사유
				}
				$mod_type = self::ESCROW_TYPE_CANCEL_AFTER_DELIVERY;
			}

			$c_PayPlus->mf_set_modx_data( "mod_type", $mod_type );      // 원거래 변경 요청 종류

			$c_PayPlus->mf_do_tx(
				"",
				$this->home_dir(),
				$this->kcpfw_option( 'site_cd' ),
				$this->kcpfw_option( 'site_key' ),
				$tran_cd,
				"",
				$this->kcpfw_option( 'gw_url' ),
				$this->kcpfw_option( 'gw_port' ),
				"payplus_cli_slib",
				"",
				$cust_ip,
				$this->kcpfw_option( 'log_level' ),
				0,
				0,
				$this->log_path()
			); // 응답 전문 처리

			$res_cd  = $c_PayPlus->m_res_cd;  // 결과 코드
			$res_msg = iconv( 'euc-kr', 'UTF-8', $c_PayPlus->m_res_msg ); // 결과 메시지

			if ( $res_cd == "0000" ) {
				do_action( 'pafw_payment_action', 'cancelled', $order->get_total(), $order, $this );

				pafw_update_meta_data( $order, '_pafw_order_cancelled', 'yes' );
				pafw_update_meta_data( $order, '_pafw_cancel_date', current_time( 'mysql' ) );

				$order->add_order_note( __( '에스크로 환불 처리가 완료되었습니다.', 'pgall-for-woocommerce' ), true );
				$order->update_status( 'refunded', '관리자에 의해 주문이 취소 되었습니다.' );

				return "success";
			} else {
				throw new Exception( sprintf( __( '에스크로 환불 처리중 오류가 발생했습니다.[%s] %s', 'pgall-for-woocommerce' ), $res_cd, $res_msg ) );
			}
		}

	}

endif;