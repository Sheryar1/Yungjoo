<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Inicis_Stdbank' ) ) {

		class WC_Gateway_Inicis_Stdbank extends WC_Gateway_Inicis {

			public function __construct() {

				$this->id = 'inicis_stdbank';

				parent::__construct();

				$this->settings['gopaymethod'] = 'directbank';
				$this->settings['paymethod']   = 'bank';

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '실시간 계좌이체', 'pgall-for-woocommerce' );
					$this->description = __( '구글크롬, IE, Safari 에서 결제 가능한 웹표준 결제 입니다 결제를 진행해 주세요.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}

			}
			function process_standard( $order, $result_map ) {
				pafw_update_meta_data( $order, '_pafw_bank_code', $result_map['ACCT_BankCode'] );
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

			function get_cash_receipts( $order ) {
				$cash_receipts = pafw_get_meta( $order, '_pafw_cash_receipts' );

				return '' == $cash_receipts ? '미발행' : '발행';
			}
		}
	}

} // class_exists function end
