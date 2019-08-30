<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Kcp_Card' ) ) :

	class WC_Gateway_Kcp_Card extends WC_Gateway_Kcp {

		public function __construct() {
			$this->id = 'kcp_card';

			parent::__construct();

			$this->settings['pc_paymethod']     = '100000000000';
			$this->settings['mobile_paymethod'] = 'card';
			$this->settings['bills_cmd']        = 'card_bill';

			if ( empty( $this->settings['title'] ) ) {
				$this->title       = __( '신용카드 결제', 'pgall-for-woocommerce' );
				$this->description = __( '구글크롬, IE, Safari 에서 결제 가능한 웹표준 결제 입니다 결제를 진행해 주세요.', 'pgall-for-woocommerce' );
			} else {
				$this->title       = $this->settings['title'];
				$this->description = $this->settings['description'];
			}
		}
		public function process_payment_result( $order, $c_PayPlus ) {
			$transaction_id = $c_PayPlus->mf_get_res_data( "tno" );
			$card_no        = $c_PayPlus->mf_get_res_data( "card_no" ); // 카드사 코드
			$card_cd        = $c_PayPlus->mf_get_res_data( "card_cd" ); // 카드사 코드
			$card_name      = $c_PayPlus->mf_get_res_data( "card_name" ); // 카드 종류
			$acqu_cd        = $c_PayPlus->mf_get_res_data( "acqu_cd" ); // 카드 종류
			$card_name      = iconv( 'euc-kr', 'UTF-8', $card_name );

			pafw_update_meta_data( $order, "_pafw_card_num", $card_no );          //카드번호
			pafw_update_meta_data( $order, "_pafw_card_code", $card_cd );        //신용카드사 코드
			pafw_update_meta_data( $order, "_pafw_card_bank_code", $acqu_cd );        //신용카드 발급사 코드
			pafw_update_meta_data( $order, "_pafw_card_name", $card_name );    //신용카드사명

			$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
				'거래번호' => $transaction_id
			) );
		}
	}

endif;