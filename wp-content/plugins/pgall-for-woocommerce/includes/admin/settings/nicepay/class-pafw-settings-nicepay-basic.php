<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Nicepay_Basic' ) ) {

	class PAFW_Settings_Nicepay_Basic extends PAFW_Settings_Nicepay {

		function get_setting_fields() {
			return array (
				array (
					'type'     => 'Section',
					'title'    => '기본 설정',
					'elements' => array (
						array (
							'id'       => 'pc_pay_method',
							'title'    => '결제수단',
							'default'  => 'nicepay_card,nicepay_bank,nicepay_vbank',
							'type'     => 'Select',
							'multiple' => 'true',
							'options'  => WC_Gateway_PAFW_Nicepay::get_supported_payment_methods()
						),
					)
				),
				array (
					'type'     => 'Section',
					'title'    => '일반결제 설정',
					'showIf'   => array ( 'pc_pay_method' => 'nicepay_card,nicepay_bank,nicepay_vbank,nicepay_escrow_bank' ),
					'elements' => array (
						array (
							'id'         => 'operation_mode',
							'title'      => '운영 모드',
							'className'  => '',
							'type'       => 'Select',
							'default'    => 'production',
							'allowEmpty' => false,
							'options'    => array (
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
							'id'        => 'merchant_id',
							'title'     => '상점 아이디',
							'className' => 'fluid',
							'default'   => 'nicepay00m',
							'desc2'     => __( '<div class="desc2">결제 테스트용 상점 아이디는 <code>nicepay00m</code> 입니다.<br>실 결제용 상점 아이디는 <code>cdm</code>으로 시작해야 합니다.</div>', 'pgall-for-woocommerce' ),
							'type'      => 'Text'
						),
						array (
							'id'        => 'merchant_key',
							'title'     => '상점키',
							'className' => 'fluid',
							'default'   => 'EYzu8jGGMfqaDEp76gSckuvnaHHu+bC4opsSN6lHv3b2lurNYkVXrZ7Z1AoqQnXI3eLuaUFyoRNC6FkrzVjceg==',
							'desc2'     => __( '<div class="desc2">결제 테스트용 상점키는 <code>EYzu8jGGMfqaDEp76gSckuvnaHHu+bC4opsSN6lHv3b2lurNYkVXrZ7Z1AoqQnXI3eLuaUFyoRNC6FkrzVjceg==</code> 입니다.</div>', 'pgall-for-woocommerce' ),
							'type'      => 'Text'
						),
						array (
							'id'        => 'cancel_pw',
							'title'     => '거래취소 비밀번호',
							'className' => 'fluid',
							'default'   => '123456',
							'desc2'     => __( '<div class="desc2">거레취소시 사용되는 비밀번호로 가맹점 관리자 페이지에서 발급 받아 설정해 주세요.<br>결제 테스트용 거래취소 비밀번호는 <b>123456</b> 입니다.</div>', 'pgall-for-woocommerce' ),
							'type'      => 'Text'
						)
					)
				),
				array (
					'type'     => 'Section',
					'title'    => '정기결제 설정',
					'showIf'   => array ( 'pc_pay_method' => 'nicepay_subscription' ),
					'elements' => array (
						array (
							'id'        => 'operation_mode_subscription',
							'title'     => '동작모드',
							'className' => '',
							'type'      => 'Select',
							'default'   => 'sandbox',
							'options'   => array (
								'sandbox'    => '개발모드(Sandbox)',
								'production' => '운영모드(Production)'
							)
						),
						array (
							'id'          => 'test_user_id_subscription',
							'title'       => '테스트 사용자 아이디',
							'className'   => 'fluid',
							'placeHolder' => '테스트 사용자 아이디를 선택하세요.',
							'showIf'      => array ( 'operation_mode_subscription' => 'sandbox' ),
							'type'        => 'Text',
							'default'     => 'pgall_test_user',
							'desc2'       => __( '<div class="desc2">개발환경(Sandbox) 모드에서는 관리자 및 테스트 사용자에게만 결제수단이 노출됩니다.</div>', 'pgall-for-woocommerce' ),
						),
						array (
							'id'        => 'subscription_merchant_id',
							'title'     => '상점아이디',
							'className' => 'fluid',
							'default'   => 'nictest04m',
							'type'      => 'Text',
							'desc2'     => __( '<div class="desc2">결제 테스트용 상점 아이디는 <code>nictest04m</code> 입니다.<br>실 결제용 상점 아이디는 <code>cdm</code>으로 시작해야 합니다.</div>', 'pgall-for-woocommerce' ),
						),
						array (
							'id'        => 'subscription_merchant_key',
							'title'     => '상점키',
							'className' => 'fluid',
							'default'   => 'b+zhZ4yOZ7FsH8pm5lhDfHZEb79tIwnjsdA0FBXh86yLc6BJeFVrZFXhAoJ3gEWgrWwN+lJMV0W4hvDdbe4Sjw==',
							'type'      => 'Text',
							'desc2'     => __( '<div class="desc2">결제 테스트용 상점키는 <code>b+zhZ4yOZ7FsH8pm5lhDfHZEb79tIwnjsdA0FBXh86yLc6BJeFVrZFXhAoJ3gEWgrWwN+lJMV0W4hvDdbe4Sjw==</code> 입니다.</div>', 'pgall-for-woocommerce' ),
						),
						array (
							'id'        => 'subscription_cancel_pw',
							'title'     => '취소비밀번호',
							'className' => 'fluid',
							'default'   => '123456',
							'type'      => 'Text',
							'desc2'     => __( '<div class="desc2">거레취소시 사용되는 비밀번호로 가맹점 관리자 페이지에서 발급 받아 설정해 주세요.<br>결제 테스트용 거래취소 비밀번호는 <b>123456</b> 입니다.</div>', 'pgall-for-woocommerce' ),
						),
						array (
							'id'        => 'management_batch_key',
							'title'     => '빌키 관리',
							'className' => '',
							'type'      => 'Select',
							'default'   => 'subscription',
							'options'   => array (
								'subscription' => '정기결제권',
								'user'         => '사용자'
							)
						)
					)
				),
			);
		}
	}
}
