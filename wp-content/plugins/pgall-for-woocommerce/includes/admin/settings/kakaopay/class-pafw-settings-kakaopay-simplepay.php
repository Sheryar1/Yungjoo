<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_KakaoPay_Simplepay' ) ) {
	class PAFW_Settings_KakaoPay_Simplepay extends PAFW_Settings_KakaoPay {
		function get_setting_fields() {
			return array (
				array (
					'type'     => 'Section',
					'title'    => '카카오페이 간편결제 설정',
					'elements' => array (
						array (
							'id'        => 'kakaopay_simplepay_title',
							'title'     => '결제수단 이름',
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => '카카오페이 간편결제',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 선택하는 결제수단명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array (
							'id'        => 'kakaopay_simplepay_description',
							'title'     => '결제수단 설명',
							'className' => 'fluid',
							'type'      => 'TextArea',
							'default'   => '카카오페이 간편결제로 결제합니다.',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 제공되는 결제수단 상세설명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						)
					)
				),
			);
		}
	}
}
