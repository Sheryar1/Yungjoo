<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Inicis_StdHpp' ) ) {

		class WC_Gateway_Inicis_StdHpp extends WC_Gateway_Inicis {

			public function __construct() {
				$this->id = 'inicis_stdhpp';

				parent::__construct();

				$this->settings['gopaymethod'] = 'HPP';
				$this->settings['paymethod']   = 'mobile';

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '휴대폰 소액결제', 'pgall-for-woocommerce' );
					$this->description = __( '휴대폰 소액결제는 14세 미만 미성년자의 경우 사용이 불가능합니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}
			}

			function process_standard( $order, $result_map ) {
				//카드관련 추가정보 추가
				pafw_update_meta_data( $order, "_pafw_hpp_num", $result_map['HPP_Num'] );

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
					'이니시스 거래번호' => $result_map['tid'],
					'몰 고유 주문번호' => $result_map['MOID']
				) );
			}
			function process_mobile_next( $order, $inimx ) {

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
					'이니시스 거래번호' => $inimx->m_tid,
					'몰 고유 주문번호' => $inimx->m_moid
				) );
			}
		}
	}

}
