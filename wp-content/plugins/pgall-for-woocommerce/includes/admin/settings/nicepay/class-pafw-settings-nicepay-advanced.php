<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Nicepay_Advanced' ) ) {
	class PAFW_Settings_Nicepay_Advanced extends PAFW_Settings_Nicepay {
		function get_setting_fields() {
			return array (
				array (
					'type'     => 'Section',
					'title'    => '고급 설정',
					'elements' => array (
						array (
							'id'        => 'payment_tag',
							'title'     => __( '결제 페이지 태그 설정', 'pgall-for-woocommerce' ),
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => '#order_review input[name=payment_method]:checked',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지가 우커머스 기본 결제 태그와 다른 경우, 결제수단 확인이 가능한 별도 태그를 넣어 지정할 수 있습니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array (
							'id'        => 'show_save_button',
							'title'     => __( '변경사항 버튼노출', 'pgall-for-woocommerce' ),
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'no',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '우커머스 기본 설정 변경 버튼을 노출합니다. 설정된 경우 버튼이 노출되며 설정되지 않은 경우 버튼이 노출되지 않습니다. 특수한 경우에만 사용하도록 제공되는 옵션으로 일반적인 경우 사용하지 않아도 됩니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
					)
				)
			);
		}
	}
}
