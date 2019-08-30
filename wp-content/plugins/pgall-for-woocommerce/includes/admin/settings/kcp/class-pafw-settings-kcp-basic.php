<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Kcp_Basic' ) ) {
	class PAFW_Settings_Kcp_Basic extends PAFW_Settings_Kcp {
		function get_setting_fields() {
			return array (
				array (
					'type'     => 'Section',
					'title'    => '기본 설정',
					'elements' => array (
						array (
							'id'       => 'pc_pay_method',
							'title'    => '결제수단',
							'default'  => 'kcp_card,kcp_bank,kcp_vbank',
							'type'     => 'Select',
							'multiple' => 'true',
							'options'  => WC_Gateway_PAFW_Kcp::get_supported_payment_methods()
						)
					)
				),
				array (
					'type'     => 'Section',
					'title'    => '운영 설정',
					'elements' => array (
						array (
							'id'        => 'operation_mode',
							'title'     => '운영 모드',
							'className' => '',
							'type'      => 'Select',
							'default'   => 'sandbox',
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
							'id'        => 'site_cd',
							'title'     => '사이트코드',
							'className' => 'fluid',
							'default'   => self::$sandbox['site_cd'],
							'type'      => 'Text',
							'desc2'     => __( '<div class="desc2">결제 테스트용 사이트코드는 <code>' . self::$sandbox['site_cd'] . '</code>입니다.<br>실 결제용 사이트코드는 <code>CO</code> 또는 <code>CM</code>으로 시작해야 합니다.</div>', 'pgall-for-woocommerce' ),
						),
						array (
							'id'        => 'site_key',
							'title'     => '사이트키',
							'className' => 'fluid',
							'default'   => self::$sandbox['site_key'],
							'type'      => 'Text',
							'desc2'     => __( '<div class="desc2">결제 테스트용 사이트키는 <code>' . self::$sandbox['site_key'] . '</code>입니다.</div>', 'pgall-for-woocommerce' ),
						)
					)
				)
			);
		}
	}
}
