<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Inicis_StdSamsungpay' ) ) {

		class WC_Gateway_Inicis_StdSamsungpay extends WC_Gateway_Inicis {

			public function __construct() {
				$this->id = 'inicis_stdsamsungpay';

				parent::__construct();

				$this->settings['gopaymethod'] = 'onlyssp';
				$this->settings['paymethod']   = 'wcard';

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '삼성페이 결제', 'pgall-for-woocommerce' );
					$this->description = __( '삼성페이를 통해 결제를 진행합니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}
			}
			function process_standard( $order, $result_map ) {
				//카드관련 추가정보 추가
				pafw_update_meta_data( $order, "_pafw_card_num", $result_map['CARD_Num'] );          //카드번호
				pafw_update_meta_data( $order, "_pafw_card_code", $result_map['CARD_Code'] );        //신용카드사 코드
				pafw_update_meta_data( $order, "_pafw_card_name", $this->get_card_name( $result_map['CARD_Code'] ) );    //신용카드사명

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
					'이니시스 거래번호' => $result_map['tid'],
					'몰 고유 주문번호' => $result_map['MOID']
				) );
			}
			function process_mobile_noti( $order = null ) {
				pafw_update_meta_data( $order, '_pafw_card_num', $_REQUEST['P_CARD_NUM'] );
				pafw_update_meta_data( $order, '_pafw_card_code', $_REQUEST['P_FN_CD1'] );
				pafw_update_meta_data( $order, '_pafw_card_name', mb_convert_encoding( $_REQUEST['P_FN_NM'], 'UTF-8', 'EUC-KR' ) );

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
					'이니시스 거래번호' => $_REQUEST['P_TID'],
					'몰 고유 주문번호' => $_REQUEST['P_OID']
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
		}
	}

} // class_exists function end
