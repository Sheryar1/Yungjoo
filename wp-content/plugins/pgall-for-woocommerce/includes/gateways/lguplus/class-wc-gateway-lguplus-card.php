<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Lguplus_Card' ) ) {

		class WC_Gateway_Lguplus_Card extends WC_Gateway_Lguplus {

			public function __construct() {
				$this->id = 'lguplus_card';

				parent::__construct();

				$this->settings['paymethod'] = 'SC0010';

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '신용카드', 'pgall-for-woocommerce' );
					$this->description = __( '카드사를 통해 결제를 진행합니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}
			}
			public function process_payment_success( $order, $xpay ) {
				pafw_update_meta_data( $order, "_pafw_card_num", $xpay->Response( 'LGD_CARDNUM', 0 ) );          //카드번호
				pafw_update_meta_data( $order, "_pafw_card_qouta", $xpay->Response( 'LGD_CARDNOINTEREST_YN', 0 ) );      //할부기간
				pafw_update_meta_data( $order, "_pafw_card_code", $xpay->Response( 'LGD_CARDACQUIRER', 0 ) );        //신용카드사 코드
				pafw_update_meta_data( $order, "_pafw_card_name", mb_convert_encoding( $xpay->Response( 'LGD_FINANCENAME', 0 ), "UTF-8", "CP949" ) );    //신용카드사명

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
					'LG유플러스 거래번호' => $xpay->Response( 'LGD_TID', 0 ),
					'몰 고유 주문번호'   => $xpay->Response( 'LGD_OID', 0 )
				) );
			}
		}
	}

} // class_exists function end
