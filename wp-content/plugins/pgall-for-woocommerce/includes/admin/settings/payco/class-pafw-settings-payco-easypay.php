<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Payco_Easypay' ) ) {
	class PAFW_Settings_Payco_Easypay extends PAFW_Settings_Payco {
		function get_setting_fields() {
			return array (
				array (
					'type'     => 'Section',
					'title'    => 'PAYCO 간편결제 설정',
					'elements' => array (
						array (
							'id'        => 'payco_easypay_title',
							'title'     => '결제수단 이름',
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => 'PAYCO 간편결제',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 선택하는 결제수단명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array (
							'id'        => 'payco_easypay_description',
							'title'     => '결제수단 설명',
							'className' => 'fluid',
							'type'      => 'TextArea',
							'default'   => 'PAYCO 간편결제로 결제합니다.',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 제공되는 결제수단 상세설명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array (
							"id"        => "vbank_account_date_limit",
							"title"     => "가상계좌 입금기한",
							"className" => "",
							"type"      => "LabeledInput",
							'inputType' => 'number',
							"leftLabel" => "계좌 발급일로부터",
							"label"     => "일",
							"default"   => "3"
						),
					)
				),
			);
		}
	}
}
