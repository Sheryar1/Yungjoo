<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_KCP_Advanced' ) ) {
	class PAFW_Settings_KCP_Advanced extends PAFW_Settings_KCP {
		function get_setting_fields() {
			return array (
				array (
					'type'     => 'Section',
					'title'    => '고급 설정',
					'elements' => array (
						array (
							'id'        => 'eng_flag',
							'title'     => __( '결제창 언어', 'pgall-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'N',
							'options'   => array (
								'N' => '한국어',
								'Y' => '영어'
							),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제창의 언어를 설정합니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array (
							'id'        => 'site_logo',
							'title'     => __( '사이트 로고', 'pgall-for-woocommerce' ),
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => PAFW()->plugin_url() . '/assets/images/default-logo.jpg',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 창 왼쪽 상단에 가맹점 사이트의 로고를 표시합니다. 가맹점의 로고가 있는 URL을 정확히 입력하셔야 하며, 입력하지 않으면 사이트명이 표시됩니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array (
							'id'        => 'skin_indx',
							'title'     => __( '스킨 설정', 'pgall-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => '1',
							'options'   => array (
								'1'  => '1',
								'2'  => '2',
								'3'  => '3',
								'4'  => '4',
								'5'  => '5',
								'6'  => '6',
								'7'  => '7',
								'8'  => '8',
								'9'  => '9',
								'10' => '10',
								'11' => '11'
							),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제창의 스킨을 설정합니다.', 'pgall-for-woocommerce' ),
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
