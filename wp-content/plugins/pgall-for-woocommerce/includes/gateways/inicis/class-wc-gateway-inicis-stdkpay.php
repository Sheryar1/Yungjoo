<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Inicis_StdKpay' ) ) {

		class WC_Gateway_Inicis_StdKpay extends WC_Gateway_Inicis {
			public function __construct() {
				$this->id = 'inicis_stdkpay';

				parent::__construct();

				$this->settings['gopaymethod'] = 'kpay';
				$this->settings['paymethod']   = 'wcard';

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( 'KPAY(간편결제)', 'pgall-for-woocommerce' );
					$this->description = __( 'KPAY는 모바일 및 PC에서 결제가 가능하나 크롬브라우저 및 맥환경에서는 사용할 수 없습니다.', 'pgall-for-woocommerce' );
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
		}
	}

} // class_exists function end
