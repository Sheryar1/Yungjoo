<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Admin_Settings' ) ) :

	class PAFW_Admin_Settings {

		static $order_statuses = null;

		static function update_settings() {
			$_REQUEST = array_merge( $_REQUEST, json_decode( stripslashes( $_REQUEST['values'] ), true ) );

			PAFW_SettingHelper::update_settings( self::get_basic_setting() );

			wp_send_json_success();
		}
		static function get_order_statuses() {
			if ( is_null( self::$order_statuses ) ) {
				self::$order_statuses = array ();

				foreach ( wc_get_order_statuses() as $status => $status_name ) {
					$status = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;

					self::$order_statuses[ $status ] = $status_name;
				}

			}

			return self::$order_statuses;
		}
		static function filter_order_statuses( $except_list ) {
			return array_diff_key( self::get_order_statuses(), array_flip( $except_list ) );
		}

		static function get_basic_setting() {
			if ( class_exists( 'MSHOP_MCommerce_Premium' ) || class_exists( 'MC_MShop' ) ) {
				$base_url = admin_url( '/admin.php?page=mshop_payment&tab=checkout&section=' );
			} else {
				$base_url = admin_url( '/admin.php?page=wc-settings&tab=checkout&section=' );
			}

			return array (
				'type'     => 'Page',
				'class'    => 'active',
				'title'    => __( '기본 설정', 'pgall-for-woocommerce' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( '결제 대행사 선택', 'pgall-for-woocommerce' ),
						'elements' => apply_filters( 'pafw_admin_gateway_settings', array (
							array (
								"id"        => "pafw-gw-inicis",
								"title"     => "KG 이니시스",
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">이니시스 일반결제 및 간편결제를 이용합니다. (신용카드, 실시간 계좌이체, 가상계좌, KPAY 간편결제, 삼성페이, 휴대폰 소액결제, 에스크로)</div>', 'pgall-for-woocommerce' ),
								"action"    => array (
									"icon"   => "cogs",
									"show"   => "yes",
									"target" => "_blank",
									"url"    => $base_url . 'mshop_inicis'
								)
							),
							array (
								"id"        => "pafw-gw-kakaopay",
								"title"     => "카카오페이",
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">카카오페이 간편결제 및 정기결제를 이용합니다.</div>', 'pgall-for-woocommerce' ),
								"action"    => array (
									"icon"   => "cogs",
									"show"   => "yes",
									"target" => "_blank",
									"url"    => $base_url . 'mshop_kakaopay'
								)
							),
							array (
								"id"        => "pafw-gw-kcp",
								"title"     => "NHN KCP",
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">KCP 일반결제를 이용합니다. (신용카드, 실시간 계좌이체, 가상계좌, 에스크로)</div>', 'pgall-for-woocommerce' ),
								"action"    => array (
									"icon"   => "cogs",
									"show"   => "yes",
									"target" => "_blank",
									"url"    => $base_url . 'mshop_kcp'
								)
							),
							array (
								"id"        => "pafw-gw-payco",
								"title"     => "NHN 페이코",
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">페이코 간편결제를 이용합니다.</div>', 'pgall-for-woocommerce' ),
								"action"    => array (
									"icon"   => "cogs",
									"show"   => "yes",
									"target" => "_blank",
									"url"    => $base_url . 'mshop_payco'
								)
							),
							array (
								"id"        => "pafw-gw-nicepay",
								"title"     => "나이스페이",
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">나이스페이 일반결제 및 정기결제를 이용합니다. (신용카드, 실시간 계좌이체, 가상계좌, 에스크로, 정기결제)</div>', 'pgall-for-woocommerce' ),
								"action"    => array (
									"icon"   => "cogs",
									"show"   => "yes",
									"target" => "_blank",
									"url"    => $base_url . 'mshop_nicepay'
								)
							),
							array (
								"id"        => "pafw-gw-lguplus",
								"title"     => "LG유플러스",
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">LG 유플러스 일반결제를 이용합니다. (신용카드, 실시간 계좌이체, 가상계좌, 에스크로)</div>', 'pgall-for-woocommerce' ),
								"action"    => array (
									"icon"   => "cogs",
									"show"   => "yes",
									"target" => "_blank",
									"url"    => $base_url . 'mshop_lguplus'
								)
							)  )
						, $base_url)
					),
					array (
						'type'     => 'Section',
						'title'    => __( '주문 상태 자동 변경 설정', 'pgall-for-woocommerce' ),
						'elements' => array (
							array (
								'id'        => 'pafw-gw-order_status_after_payment',
								'title'     => __( '결제(입금) 완료', 'pgall-for-woocommerce' ),
								'className' => '',
								'type'      => 'Select',
								'default'   => 'processing',
								'options'   => self::filter_order_statuses( array (
									'pending',
									'on-hold',
									'cancelled',
									'failed',
									'refunded',
                                    'exchange-request',
                                    'accept-exchange',
                                    'return-request',
                                    'accept-return',
                                    'cancel-request'
								) ),
								'tooltip'   => array (
									'title' => array (
										'content' => __( '결제(입금)이 완료된 경우, 지정된 주문상태로 변경합니다.', 'pgall-for-woocommerce' ),
									)
								)
							),
							array (
								'id'        => 'pafw-gw-order_status_after_vbank_payment',
								'title'     => __( '가상계좌 주문접수', 'pgall-for-woocommerce' ),
								'className' => '',
								'type'      => 'Select',
								'default'   => 'on-hold',
								'options'   => self::filter_order_statuses( array (
									'pending',
									'cancelled',
									'failed',
									'refunded',
									'completed',
									'exchange-request',
									'accept-exchange',
									'return-request',
									'accept-return',
									'cancel-request'
								) ),
								'tooltip'   => array (
									'title' => array (
										'content' => __( '가상계좌 결제수단으로 주문이 접수된 경우, 지정된 주문상태로 변경합니다.', 'pgall-for-woocommerce' ),
									)
								)
							),
							array (
								'id'        => 'pafw-gw-order_status_after_enter_shipping_number',
								'title'     => __( '에스크로 배송정보 등록', 'pgall-for-woocommerce' ),
								'className' => '',
								'type'      => 'Select',
								'default'   => 'shipped',
								'options'   => self::filter_order_statuses( array (
									'pending',
									'on-hold',
									'cancelled',
									'failed',
									'refunded',
									'completed',
									'exchange-request',
									'accept-exchange',
									'return-request',
									'accept-return',
									'cancel-request'
								) ),
								'tooltip'   => array (
									'title' => array (
										'content' => __( '관리자가 에스크로 결제건의 배송정보를 등록한 경우, 지정된 주문상태로 변경합니다.', 'pgall-for-woocommerce' ),
									)
								)
							)
						)
					),
					array (
						'type'     => 'Section',
						'title'    => __( '구매자 주문처리 가능 상태 설정', 'pgall-for-woocommerce' ),
						'elements' => array (
							array (
								'id'        => 'pafw-gw-possible_refund_status_for_mypage',
								'title'     => __( '주문취소', 'pgall-for-woocommerce' ),
								'className' => '',
								'type'      => 'Select',
								'default'   => 'pending,on-hold',
								'multiple'  => true,
								'options'   => self::filter_order_statuses( array (
									'cancelled',
									'failed',
									'refunded',
								) ),
								'tooltip'   => array (
									'title' => array (
										'content' => __( '구매자가 내계정 페이지의 주문 목록 화면에서 주문을 취소할 수 있는 주문 상태를 지정합니다.', 'pgall-for-woocommerce' ),
									)
								)
							),
							array (
								'id'        => 'pafw-gw-possible_escrow_confirm_status_for_customer',
								'title'     => __( '에스크로 구매 확인 및 거절', 'pgall-for-woocommerce' ),
								'className' => '',
								'type'      => 'Select',
								'default'   => 'shipped,cancel-request',
								'multiple'  => true,
								'options'   => self::filter_order_statuses( array (
									'pending',
									'on-hold',
									'cancelled',
									'failed',
									'refunded',
								) ),
								'tooltip'   => array (
									'title' => array (
										'content' => __( '구매자가 내계정 페이지의 주문 상세 화면에서 에스크로 결제건에 대한 구매 확인 및 거절 처리를 할 수 있는 주문 상태를 지정합니다.', 'pgall-for-woocommerce' ),
									)
								)
							)
						)
					),
					array (
						'type'     => 'Section',
						'title'    => __( '교환 및 반품', 'pgall-for-woocommerce' ),
						'elements' => array (
							array (
								'id'        => 'pafw-gw-support-exchange',
								'title'     => __( '교환 지원', 'pgall-for-woocommerce' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "yes",
								"desc"      => __( '<div class="desc2">고객이 구매한 상품에 대해 교환을 요청할 수 있습니다.</div>', 'pgall-for-woocommerce' ),
							),
							array (
								'id'        => 'pafw-gw-support-return',
								'title'     => __( '반품 지원', 'pgall-for-woocommerce' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "yes",
								"desc"      => __( '<div class="desc2">고객이 구매한 상품에 대해 반품을 요청할 수 있습니다.</div>', 'pgall-for-woocommerce' ),
							),
							array (
								"id"        => "pafw-gw-ex-terms",
								"title"     => "교환/반품 허용 기간",
								"showIf"    => array ( array ( 'pafw-gw-support-exchange' => 'yes', 'pafw-gw-support-return' => 'yes' ) ),
								"className" => "",
								"type"      => "LabeledInput",
								'inputType' => 'number',
								"leftLabel" => "배송완료 또는 주문처리완료 후",
								"label"     => "일",
								"default"   => "3"
							)
						)
					),
					array (
						'type'     => 'Section',
						'title'    => __( '재고관리', 'pgall-for-woocommerce' ),
						'elements' => array (
							array (
								'id'        => 'pafw-gw-support-cancel-unpaid-order',
								'title'     => __( '무통장입금 재고관리', 'pgall-for-woocommerce' ),
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">무통장입금 ( BACS, 가상계좌 ) 결제건에 대한 재고관리 기능을 사용합니다.<br>지정된 시간내에 입금되지 않은 결제건은 자동 취소처리되며, 재고관리가 활성화 된 경우, 상품의 재고가 다시 복원됩니다.</div>', 'pgall-for-woocommerce' ),
							),
							array (
								"id"        => "pafw-gw-cancel-unpaid-order-days",
								"title"     => "무통장입금 대기 시간",
								"showIf"    => array ( 'pafw-gw-support-cancel-unpaid-order' => 'yes' ),
								"className" => "",
								"type"      => "LabeledInput",
								'inputType' => 'number',
								"leftLabel" => "결제 후",
								"label"     => "일",
								"default"   => "3"
							)
						)
					)
				)
			);
		}

		static function get_setting_for_tac() {
			return array (
				'type'     => 'Page',
				'class'    => 'active',
				'title'    => __( '기본 설정', 'pgall-for-woocommerce' ),
				'elements' => array (
					array (
						"type"      => "Accordion",
						"className" => "fluid",
						"elements"  => array (
							array (
								"id"        => "agreement1",
								"title"     => __( "PGALL 워드프레스 결제 플러그인  이용약관", 'pgall-for-woocommerce' ),
								"className" => "fluid active",
								"type"      => "TextArea",
								'readonly'  => 'yes',
								'rows'      => 20,
								'default'   => file_get_contents( PAFW()->plugin_path() . '/assets/data/agreement.txt' ),
							),
						)
					),
					array (
						'type'              => 'Section',
						"hideSectionHeader" => true,
						"className"         => "aaa",
						'elements'          => array (
							array (
								'id'         => 'msm_install_page',
								'label'      => '이용 약관에 동의합니다.',
								'iconClass'  => '',
								'className'  => 'fluid',
								'type'       => 'Button',
								'default'    => '',
								'actionType' => 'ajax',
								'ajaxurl'    => admin_url( 'admin-ajax.php' ),
								'action'     => PAFW()->slug() . '-agree_to_tac',
							),
						)
					),
				)
			);
		}

		static function enqueue_scripts() {
			wp_enqueue_style( 'mshop-setting-manager', PAFW()->plugin_url() . '/includes/admin/setting-manager/css/setting-manager.min.css' );
			wp_enqueue_script( 'mshop-setting-manager', PAFW()->plugin_url() . '/includes/admin/setting-manager/js/setting-manager.min.js', array (
				'underscore',
				'jquery',
				'jquery-ui-core'
			) );
		}

		public static function output() {
			if ( 'yes' == get_option( PAFW()->slug() . '-agree-to-tac', 'no' ) ) {
				self::output_settings();
			} else {
				self::output_agreements();
			}
		}

		public static function output_settings() {
			$settings = self::get_basic_setting();

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array (
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => PAFW()->slug() . '-update_pafw_settings',
				'settings' => $settings,
				'slug'     => PAFW()->slug()
			) );

			?>
            <script>
                jQuery(document).ready(function() {
                    jQuery(this).trigger('mshop-setting-manager', ['mshop-setting-wrapper', '100', <?php echo json_encode( PAFW_SettingHelper::get_settings( $settings ) ); ?>, null, null]);
                });
            </script>

            <div id="mshop-setting-wrapper"></div>
			<?php
        }

		public static function output_agreements() {
			$settings = self::get_setting_for_tac();

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array (
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => PAFW()->slug() . '-update_pafw_settings',
				'settings' => $settings,
				'slug'     => PAFW()->slug()
			) );

			?>
            <script>
                jQuery(document).ready(function() {
                    jQuery(this).trigger('mshop-setting-manager', ['mshop-setting-wrapper', '100', <?php echo json_encode( PAFW_SettingHelper::get_settings( $settings ) ); ?>, null, null]);
                });
            </script>
            <style>
                #mshop-setting-wrapper textarea {
                    font-size: 1em !important;
                    line-height: 1.5 !important;
                }
            </style>
            <h3>PGALL 워드프레스 결제 플러그인은 약관 동의 후 이용이 가능합니다.</h3>
            <div id="mshop-setting-wrapper"></div>
			<?php
        }

	}
endif;



