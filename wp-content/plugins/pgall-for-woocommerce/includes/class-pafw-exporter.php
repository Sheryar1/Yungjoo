<?php


//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Exporter' ) ) {

	class PAFW_Exporter {
		public static function get_additional_charge( $total, $order ) {
			$history = pafw_get_meta( $order, '_pafw_additional_charge_history' );

			if ( ! empty( $history ) ) {
				$total = 0;

				foreach ( $history as $tid => $item ) {
					if ( 'PAYED' == $item['status'] ) {
						$total += floatval( $item['charged_amount'] );
					}
				}
			}

			return $total;
		}
		public static function get_partial_refund( $total, $order ) {
			$history = pafw_get_meta( $order, '_pafw_repay' );

			if ( ! empty( $history ) ) {
				$total = 0;

				if( is_string( $history ) ) {
					$history = json_decode( $history, true );
				}

				foreach ( $history as $item ) {
					if ( ! empty( $item['refund_price'] ) ) {
						$total += floatval( $item['refund_price'] );
					} else if ( ! empty( $item['canceled_amount'] ) ) {
						$total = floatval( $item['canceled_amount']['total'] );
					}
				}
			}

			return $total;
		}

	}

}