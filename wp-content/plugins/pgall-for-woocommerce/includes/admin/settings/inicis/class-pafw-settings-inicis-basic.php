<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Inicis_Basic' ) ) {
	class PAFW_Settings_Inicis_Basic extends PAFW_Settings_Inicis {
		function get_setting_fields() {
			return array (
				array (
					'type'     => 'Section',
					'title'    => '기본 설정',
					'elements' => array (
						array (
							'id'       => 'pc_pay_method',
							'title'    => '결제수단',
							'default'  => 'inicis_stdcard,inicis_stdbank,inicis_stdvbank',
							'type'     => 'Select',
							'multiple' => 'true',
							'options'  => WC_Gateway_PAFW_Inicis::get_supported_payment_methods()
						)
					)
				),
				array (
					'type'     => 'Section',
					'title'    => '일반 결제 설정',
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
							'id'        => 'libfolder',
							'title'     => '이니페이 설치 경로',
							'className' => 'fluid',
							'default'   => WP_CONTENT_DIR . '/inicis',
							'type'      => 'Text',
							'desc2'     => __( '<div class="desc2">이니페이 설치 경로 안에 key 폴더(키파일)와 log 폴더(로그)가 위치한 경로를 입력해주세요. 키파일 폴더와 로그 폴더의 권한 설정은 매뉴얼을 참고해주세요.</div>', 'pgall-for-woocommerce' ),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '<span style="color:red;font-weight:bold;">[ 주의사항 ]<ul><li>호스팅이나 서버 상태에 따라 웹상에서 접근이 불가능한 경로에 상점키파일을 업로드 하신 후, 절대 경로를 입력 해 주세요.</li><li>웹상에서 접근 가능한 경로에 결제 폴더가 위치한 경우 키파일 및 로그파일 노출로 인해 보안 사고가 발생할 수 있으며, 이 경우 발생하는 문제는 상점의 책임 입니다.</li></ul></span>', 'pgall-for-woocommerce' ),
								)
							)
						),
						array (
							'id'          => 'merchant_id',
							'title'       => '상점 아이디',
							'className'   => '',
							'placeholder' => '상점 아이디를 선택하세요.',
							'type'        => 'Select',
							'default'     => 'INIpayTest',
							'options'     => $this->get_keyfile_list(),
							'desc2'       => __( '<div class="desc2">결제 테스트용 상점 아이디는 <code>INIpayTest</code> 입니다.<br>실 결제용 상점 아이디는 <code>COD</code> 또는 <code>MOD</code>로 시작해야 합니다.</div>', 'pgall-for-woocommerce' ),
						),
						array (
							'id'        => 'signkey',
							'title'     => '웹표준 사인키',
							'className' => 'fluid',
							'default'   => 'SU5JTElURV9UUklQTEVERVNfS0VZU1RS',
							'desc2'     => __( '<div class="desc2">웹표준 사인키는 결제시 필요한 필수 값으로 이니시스 상점 관리자 페이지에서 확인이 가능합니다.<br>결제 테스트용 INIpayTest 상점 아이디의 사인키 값은 <code>SU5JTElURV9UUklQTEVERVNfS0VZU1RS</code>입니다.</div>', 'pgall-for-woocommerce' ),
							'type'      => 'Text'
						)
					)
				),
				array (
					'type'     => 'Section',
					'title'    => '에스크로 결제 설정',
					'showIf'   => array ( 'pc_pay_method' => 'inicis_stdescrow_bank' ),
					'elements' => array (
						array (
							'id'          => 'escrow_merchant_id',
							'title'       => '상점 아이디',
							'className'   => '',
							'placeholder' => '상점 아이디를 선택하세요.',
							'type'        => 'Select',
							'options'     => $this->get_keyfile_list()
						),
						array (
							'id'        => 'escrow_signkey',
							'title'     => '웹표준 사인키',
							'className' => 'fluid',
							'default'   => 'SU5JTElURV9UUklQTEVERVNfS0VZU1RS',
							'desc2'     => __( '<div class="desc2">웹표준 사인키는 결제시 필요한 필수 값으로 이니시스 상점 관리자 페이지에서 확인이 가능합니다.<br>결제 테스트용 iniescrow0 상점 아이디의 사인키 값은 <code>SU5JTElURV9UUklQTEVERVNfS0VZU1RS</code>입니다.</div>', 'pgall-for-woocommerce' ),
							'type'      => 'Text'
						)
					)
				)
			);
		}
	}
}
