<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Nicepay_Card' ) ) {

		class WC_Gateway_Nicepay_Card extends WC_Gateway_Nicepay {

			public function __construct() {
				$this->id = 'nicepay_card';

				parent::__construct();

				$this->settings['paymethod'] = 'CARD';

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '신용카드', 'pgall-for-woocommerce' );
					$this->description = __( '카드사를 통해 결제를 진행합니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}

				$this->success_code = '3001';
			}
			public function process_standard( $order, $responseDTO ) {
				$transaction_id = $responseDTO->getParameter( "TID" );
				$txnid          = $responseDTO->getParameter( "Moid" );
				$card_num       = $responseDTO->getParameter( "CardNo" );
				$card_code      = $responseDTO->getParameter( "CardCode" );
				$card_name      = mb_convert_encoding( $responseDTO->getParameter( "CardName" ), "UTF-8", "CP949" );

				pafw_update_meta_data( $order, "_pafw_card_num", $card_num );
				pafw_update_meta_data( $order, "_pafw_card_code", $card_code );
				pafw_update_meta_data( $order, "_pafw_card_name", $card_name );

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
					'거래번호' => $transaction_id
				) );
			}
		}
	}

} // class_exists function end
