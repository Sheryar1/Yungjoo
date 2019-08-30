<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Kcp_Bank' ) ) :

	class WC_Gateway_Kcp_Bank extends WC_Gateway_Kcp {

		public function __construct() {
			$this->id = 'kcp_bank';

			parent::__construct();

			$this->settings['pc_paymethod']     = '010000000000';
			$this->settings['mobile_paymethod'] = 'acnt';
			$this->settings['bills_cmd']        = 'acnt_bill';

			if ( empty( $this->settings['title'] ) ) {
				$this->title       = __( '실시간 계좌이체', 'pgall-for-woocommerce' );
				$this->description = __( '실시간 계좌이체를 통해 결제를 할 수 있습니다.', 'pgall-for-woocommerce' );
			} else {
				$this->title       = $this->settings['title'];
				$this->description = $this->settings['description'];
			}
		}
		public function process_payment_result( $order, $c_PayPlus ) {
			$transaction_id = $c_PayPlus->mf_get_res_data( "tno" );
			$bank_code      = $c_PayPlus->mf_get_res_data( "bank_code" );
			$bank_name      = iconv( 'euc-kr', 'UTF-8', $c_PayPlus->mf_get_res_data( "bank_name" ) );
			$cash_authno    = $c_PayPlus->mf_get_res_data( "cash_authno" );

			pafw_update_meta_data( $order, '_pafw_bank_code', $bank_code );
			pafw_update_meta_data( $order, '_pafw_bank_name', $bank_name );
			pafw_update_meta_data( $order, '_pafw_cash_receipts', ! empty( $cash_authno ) ? '발행' : '미발행' );

			$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
				'거래번호' => $transaction_id
			) );
		}
	}

endif;