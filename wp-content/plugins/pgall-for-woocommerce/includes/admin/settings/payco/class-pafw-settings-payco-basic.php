<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Payco_Basic' ) ) {
	class PAFW_Settings_Payco_Basic extends PAFW_Settings_Payco {
		function get_setting_fields() {
			return array (
				array (
					'type'     => 'Section',
					'title'    => '기본 설정',
					'elements' => array (
						array (
							'id'       => 'pc_pay_method',
							'title'    => '결제수단',
							'default'  => 'payco_easypay',
							'type'     => 'Select',
							'multiple' => 'true',
							'options'  => WC_Gateway_PAFW_Payco::get_supported_payment_methods()
						),
					)
				),
				array (
					'type'     => 'Section',
					'title'    => '일반 결제 설정',
					'elements' => array (
						array (
							'id'        => 'operation_mode',
							'title'     => '운영 모드',
							'className' => '',
							'type'      => 'Select',
							'default'   => 'production',
							'options'   => array (
								'sandbox'    => '개발환경(Sandbox)',
								'production' => '실환경(Production)'
							)
						),
						array (
							'id'          => 'test_user_id',
							'title'       => '테스트 사용자 아이디',
							'className'   => 'fluid',
							'placeHolder' => '테스트 사용자 아이디를 선택하세요.',
							'showIf'      => array ( 'operation_mode' => 'sandbox' ),
							'type'        => 'Text',
							'default'     => 'pgall_test_user',
							'desc2'       => __( '<div class="desc2">개발환경(Sandbox) 모드에서는 관리자 및 테스트 사용자에게만 결제수단이 노출됩니다.</div>', 'pgall-for-woocommerce' ),
						),
						array (
							'id'        => 'seller_key',
							'title'     => '가맹점코드 (sellerKey)',
							'className' => '',
							'type'      => 'Text',
							'default'   => 'S0FSJE',
							'desc2'     => __( '<div class="desc2">결제 테스트용 가맹점코드(sellerKey)는 <code>S0FSJE</code>입니다.<br>실 결제용 가맹점코드는 <code>CM_</code>로 시작해야 합니다.</div>', 'pgall-for-woocommerce' ),
						),
						array (
							'id'        => 'cpid',
							'title'     => '상점 아이디 (cpId)',
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => 'PARTNERTEST',
							'desc2'     => __( '<div class="desc2">결제 테스트용 상점 아이디는 <code>PARTNERTEST</code>입니다.</div>', 'pgall-for-woocommerce' ),
						),
						array (
							'id'        => 'product_id',
							'title'     => '상품 아이디 (productID)',
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => 'PROD_EASY',
							'desc2'     => __( '<div class="desc2">결제 테스트용 상품 아이디는 <code>PROD_EASY</code>입니다.</div>', 'pgall-for-woocommerce' ),
						)
					)
				)
			);
		}
	}
}
