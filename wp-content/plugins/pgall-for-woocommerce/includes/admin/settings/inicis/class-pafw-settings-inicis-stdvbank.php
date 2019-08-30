<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Inicis_Stdvbank' ) ) {
	class PAFW_Settings_Inicis_Stdvbank extends PAFW_Settings_Inicis {
		function get_setting_fields() {

			return array (
				array (
					'type'     => 'Section',
					'title'    => '가상계좌 설정',
					'elements' => array (
						array (
							'id'        => 'inicis_stdvbank_title',
							'title'     => '결제수단 이름',
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => '가상계좌 무통장입금',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 선택하는 결제수단명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array (
							'id'        => 'inicis_stdvbank_description',
							'title'     => '결제수단 설명',
							'className' => 'fluid',
							'type'      => 'TextArea',
							'default'   => __( '가상계좌 안내 후 무통장입금을 진행 해 주세요.', 'pgall-for-woocommerce' ),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 제공되는 결제수단 상세설명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array (
							'id'        => 'inicis_stdvbank_order_vbank_noti_url_new',
							'title'     => __( '입금통보 URL', 'pgall-for-woocommerce' ),
							'className' => '',
							'type'      => 'Label',
							'readonly'  => 'yes',
							'default'   => '',
							'desc2'     => __( '<code>' . untrailingslashit( WC()->api_request_url( 'WC_Gateway_Inicis_StdVbank?type=vbank_noti', pafw_check_ssl() ) ) . '</code><div class="desc2">가상계좌 무통장입금 내역 통보에 사용되는 URL 주소입니다.<br>가상계좌 무통장입금 매뉴얼을 참고하여 이니시스 가맹점 관리자 페이지에 접속하여 주소를 입력하여 주시기 바랍니다.</div>', 'pgall-for-woocommerce' ),
						),
						array (
							"id"        => "inicis_stdvbank_account_date_limit",
							"title"     => "가상계좌 입금기한",
							"className" => "",
							"type"      => "LabeledInput",
							'inputType' => 'number',
							"leftLabel" => "계좌 발급일로부터",
							"label"     => "일",
							"default"   => "3"
						),
						array (
							'id'        => 'inicis_stdvbank_receipt',
							'title'     => __( '현금 영수증', 'pgall-for-woocommerce' ),
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'no',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '현금 영수증 발행 여부를 설정 할 수 있습니다. 현금 영수증 발행은 결제 대행사와 별도 계약이 되어 있어야 이용이 가능합니다.', 'pgall-for-woocommerce' ),
								)
							)
						)
					)
				),
				array (
					'type'     => 'Section',
					'title'    => '가상계좌 고급 설정',
					'elements' => array (
						array (
							'id'        => 'inicis_stdvbank_use_advanced_setting',
							'title'     => '사용',
							'className' => '',
							'type'      => 'Toggle',
							'default'   => 'no',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '고급 설정 사용 시, 기본 설정에 우선합니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array (
							'id'        => 'inicis_stdvbank_order_status_after_vbank_payment',
							'title'     => __( '주문접수시 변경될 주문상태', 'pgall-for-woocommerce' ),
							'showIf'    => array ( 'inicis_stdvbank_use_advanced_setting' => 'yes' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'on-hold',
							'options'   => $this->filter_order_statuses( array (
								'cancelled',
								'failed',
								'refunded'
							) ),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '가상계좌 결제건에 한해서, 주문이 접수되면 지정된 주문상태로 변경합니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array (
							'id'        => 'inicis_stdvbank_order_status_after_payment',
							'title'     => __( '입금완료시 변경될 주문상태', 'pgall-for-woocommerce' ),
							'showIf'    => array ( 'inicis_stdvbank_use_advanced_setting' => 'yes' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'processing',
							'options'   => $this->filter_order_statuses( array (
								'cancelled',
								'failed',
								'on-hold',
								'refunded'
							) ),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '가상계좌 결제건에 한해서, 가상계좌 입금 통보가 수신되어 입금이 완료되면 지정된 주문상태로 변경합니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array (
							'id'        => 'inicis_stdvbank_possible_refund_status_for_mypage',
							'title'     => __( '구매자 주문취소 가능상태', 'pgall-for-woocommerce' ),
							'showIf'    => array ( 'inicis_stdvbank_use_advanced_setting' => 'yes' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'pending,on-hold',
							'multiple'  => true,
							'options'   => $this->get_order_statuses(),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '가상계좌 결제건에 한해서, 구매자가 내계정 페이지에서 주문취소 요청을 할 수 있는 주문 상태를 지정합니다.', 'pgall-for-woocommerce' ),
								)
							)
						)
					)
				)
			);
		}
	}
}
