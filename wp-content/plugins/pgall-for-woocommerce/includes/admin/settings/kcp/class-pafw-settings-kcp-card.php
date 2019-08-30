<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Kcp_Card' ) ) {
	class PAFW_Settings_Kcp_Card extends PAFW_Settings_Kcp {

		function get_setting_fields() {
			return array (
				array (
					'type'     => 'Section',
					'title'    => '신용카드 설정',
					'elements' => array (
						array (
							'id'        => 'kcp_card_title',
							'title'     => '결제수단 이름',
							'className' => 'fluid',
							'type'      => 'Text',
							'default'   => '신용카드',
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 선택하는 결제수단명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array (
							'id'        => 'kcp_card_description',
							'title'     => '결제수단 설명',
							'className' => 'fluid',
							'type'      => 'TextArea',
							'default'   => "신용카드 결제를 진행 합니다.",
							'tooltip'   => array (
								'title' => array (
									'content' => __( '결제 페이지에서 구매자들이 결제 진행 시 제공되는 결제수단 상세설명 입니다.', 'pgall-for-woocommerce' )
								)
							)
						),
						array (
							'id'        => 'kcp_noint',
							'title'     => __( '가맹점 부담 무이자 설정', 'pgall-for-woocommerce' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => '-',
							'options'   => array (
								'-' => '관리자 설정할부',
								'N' => '일반할부',
								'Y' => '설정할부',
							),
							'tooltip'   => array (
								'title' => array (
									'content' => '<div class="ui bulleted list"> <div class="item">관리자 설정할부 : 가맹점 관리자 페이지에 설정 된 무이자 정보로 결제를 시작합니다. 가맹점 관리자페이지에 설정 된 카드사의 무이자 정보를 결제 창에 보여집니다</div><div class="item">일반할부 : 가맹점 관리자 페이지의 무이자설정을 무시하고, 결제요청을 일반할부로 합니다</div><div class="item">설정할부 : 무이자 설정에 설정된 무이자 옵션을 결제 창에 보여줍니다.</div></div>'
								)
							)
						),
						array (
							'id'        => 'kcp_noint_quota',
							'title'     => __( '무이자 설정', 'pgall-for-woocommerce' ),
							'className' => '',
							'showIf'    => array ( 'kcp_noint' => 'Y' ),
							'type'      => 'SortableTable',
							"repeater"  => true,
							"sortable"  => false,
							'editable'  => true,
							'default'   => array (),
							"elements"  => array (
								array (
									"id"          => "card_company",
									"title"       => "카드사",
									"className"   => "five wide column fluid",
									"type"        => "Select",
									"placeHolder" => '카드사를 선택하세요.',
									'options'     => self::$card_company
								),
								array (
									"id"          => "month",
									"title"       => "개월수",
									"className"   => "fluid",
									"type"        => "Select",
									"placeHolder" => '할부개월수를 선택하세요.(복수선택가능)',
									"multiple"    => true,
									"options"     => self::$noint_quota_month
								)
							)
						)
					)
				),
				array (
					'type'     => 'Section',
					'title'    => '신용카드 고급 설정',
					'elements' => array (
						array (
							'id'        => 'kcp_card_use_advanced_setting',
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
							'id'        => 'kcp_card_order_status_after_payment',
							'title'     => __( '결제완료시 변경될 주문상태', 'pgall-for-woocommerce' ),
							'showIf'    => array ( 'kcp_card_use_advanced_setting' => 'yes' ),
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
									'content' => __( '신용카드 결제건에 한해서, 결제(입금)이 완료되면 지정된 주문상태로 변경합니다.', 'pgall-for-woocommerce' ),
								)
							)
						),
						array (
							'id'        => 'kcp_card_possible_refund_status_for_mypage',
							'title'     => __( '구매자 주문취소 가능상태', 'pgall-for-woocommerce' ),
							'showIf'    => array ( 'kcp_card_use_advanced_setting' => 'yes' ),
							'className' => '',
							'type'      => 'Select',
							'default'   => 'pending,on-hold',
							'multiple'  => true,
							'options'   => $this->get_order_statuses(),
							'tooltip'   => array (
								'title' => array (
									'content' => __( '신용카드 결제건에 한해서, 구매자가 내계정 페이지에서 주문취소 요청을 할 수 있는 주문 상태를 지정합니다.', 'pgall-for-woocommerce' ),
								)
							)
						)
					)
				)
			);
		}
	}
}
