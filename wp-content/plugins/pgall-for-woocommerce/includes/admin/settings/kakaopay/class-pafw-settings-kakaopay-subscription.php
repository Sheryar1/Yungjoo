<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_KakaoPay_Subscription' ) ) {
	class PAFW_Settings_KakaoPay_Subscription extends PAFW_Settings_KakaoPay {
		function get_setting_fields() {
			return array (
				array (
					'type'     => 'Section',
					'title'    => '카카오페이 정기결제 설정',
					'elements' => array (
						array (
							'id'        => 'kakaopay_subscription_title',
							'title'     => '결제수단 이름',
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => '카카오페이 정기결제',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 선택하는 결제수단명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array (
							'id'        => 'kakaopay_subscription_description',
							'title'     => '결제수단 설명',
							'className' => 'fluid',
							'type'      => 'TextArea',
							'default'   => '카카오페이 정기결제로 결제합니다.',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 제공되는 결제수단 상세설명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array (
							'id'        => 'kakaopay_subscription_support_multiple_subscriptions',
							'title'     => '정기결제상품 동시 구매 지원',
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'no',
							'tooltip'   => array (
								'title' => array (
									'content' => '복수개의 정기결제 상품을 동시에 구매할 수 있습니다.'
								)
							)
						),
						array (
							'id'        => 'kakaopay_subscription_support_products',
							'title'     => '일반(단순,옵션)상품 구매 지원',
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'no',
							'tooltip'   => array (
								'title' => array (
									'content' => '일반 상품도 구매할 수 있습니다.'
								)
							)
						),
					)
				),
			);
		}
	}
}
