<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Lguplus_Bank' ) ) {

		class WC_Gateway_Lguplus_Bank extends WC_Gateway_Lguplus {

			public function __construct() {
				$this->id = 'lguplus_bank';

				parent::__construct();

				$this->settings['paymethod'] = 'SC0030';

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '실시간계좌이체', 'pgall-for-woocommerce' );
					$this->description = __( '계좌에서 바로 결제하는 실시간 계좌이체 입니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}
			}
			public function process_payment_success( $order, $xpay ) {
				pafw_update_meta_data( $order, '_pafw_bank_code', $xpay->Response( 'LGD_FINANCECODE', 0 ) );    //입금은행코드
				pafw_update_meta_data( $order, '_pafw_bank_name', mb_convert_encoding( $xpay->Response( 'LGD_FINANCENAME', 0 ), "UTF-8", "CP949" ) );    //입금은행명/코드
				pafw_update_meta_data( $order, '_pafw_cash_receipts', $xpay->Response( 'LGD_CASHRECEIPTNUM', 0 ) );

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
					'LG유플러스 거래번호' => $xpay->Response( 'LGD_TID', 0 ),
					'몰 고유 주문번호'   => $xpay->Response( 'LGD_OID', 0 )
				) );
			}

			function get_cash_receipts( $order ) {
				$cash_receipts = pafw_get_meta( $order, '_pafw_cash_receipts' );

				return '' == $cash_receipts ? '미발행' : '발행';
			}
		}
	}

} // class_exists function end
