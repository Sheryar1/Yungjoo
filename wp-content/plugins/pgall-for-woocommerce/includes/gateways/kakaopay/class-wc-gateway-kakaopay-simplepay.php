<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_KakaoPay_Simplepay' ) ) {

		class WC_Gateway_KakaoPay_Simplepay extends WC_Gateway_KakaoPay {

			public function __construct() {

				$this->id = 'kakaopay_simplepay';

				parent::__construct();

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '카카오페이 간편결제', 'pgall-for-woocommerce' );
					$this->description = __( '카카오페이 간편결제로 결제합니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}

			}
		}
	}

} // class_exists function end
