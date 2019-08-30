<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Admin_Payment_Method_Control_Settings' ) ) :

	class PAFW_Admin_Payment_Method_Control_Settings {

		static $order_statuses = null;

		static function update_settings() {
			$_REQUEST = array_merge( $_REQUEST, json_decode( stripslashes( $_REQUEST['values'] ), true ) );

			PAFW_SettingHelper::update_settings( self::get_basic_setting() );

			wp_send_json_success();
		}

		static function get_payment_gateways() {
			$gateways = array();
			foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $gateway ) {
				$gateways[ $gateway->id ] = $gateway->get_title();
			}

			return $gateways;
		}

		static function get_language_options() {
			$languages = array();

			if ( function_exists( 'icl_object_id' ) ) {
				foreach ( wpml_get_active_languages() as $code => $language ) {
					$languages[ $code ] = $language['native_name'];
				}
			}

			return $languages;
		}

		static function get_language_control_setting() {
			if ( function_exists( 'icl_object_id' ) ) {
				return array(
					array(
						"id"        => "pafw-payment-method-by-language",
						"title"     => "",
						"className" => "",
						"editable"  => "true",
						"sortable"  => "true",
						"repeater"  => "true",
						"type"      => "SortableTable",
						"default"   => array(),
						"template"  => array(
							"enabled"         => "yes",
							"language"        => "",
							"payment_methods" => "",
						),
						"elements"  => array(
							array(
								"id"        => "enabled",
								"title"     => __( "활성화", "pgall-for-woocommerce" ),
								"className" => " one wide column fluid",
								"default"   => "yes",
								"type"      => "Toggle"
							),
							array(
								"id"          => "language",
								"title"       => __( "사이트 언어", "pgall-for-woocommerce" ),
								"className"   => " six wide column search fluid",
								'multiple'    => true,
								"type"        => "Select",
								"default"     => "",
								'options'     => self::get_language_options(),
								"placeholder" => __( "사이트 언어를 선택하세요.", "pgall-for-woocommerce" )
							),
							array(
								"id"          => "payment_methods",
								"title"       => __( "결제수단", "pgall-for-woocommerce" ),
								"className"   => " seven wide column search fluid",
								'multiple'    => true,
								'search'      => true,
								"type"        => "Select",
								"default"     => "",
								'options'     => self::get_payment_gateways(),
								"placeholder" => __( "결제수단을 선택하세요.", "pgall-for-woocommerce" )
							)
						)
					)
				);
			} else {
				return array(
					array(
						'id'       => 'pafw-payment-method-by-language-guide',
						'type'     => 'Label',
						'readonly' => 'yes',
						'default'  => '',
						'desc2'    => __( '<div class="desc2">언어별 결제수단 제어 기능은 WPML 플러그인이 설치되어 있어야 합니다.</div>', 'pgall-for-woocommerce' ),
					)
				);
			}
		}

		static function get_basic_setting() {
			return array(
				'type'     => 'Page',
				'class'    => 'active',
				'title'    => __( '기본 설정', 'pgall-for-woocommerce' ),
				'elements' => array(
					array(
						'type'           => 'Section',
						'title'          => __( '결제수단 노출제어', 'pgall-for-woocommerce' ),
						'hideSaveButton' => true,
						'elements'       => array(
							array(
								'id'        => 'pafw-payment-method-control-description',
								'title'     => '',
								'className' => '',
								'type'      => 'Label',
								'readonly'  => 'yes',
								'default'   => '',
								'desc2'     => __( '<div class="desc2">결제수단 노출 제어 정책에 지정된 상품이 장바구니에 포함된 경우, 결제 수단이 지정된 수단으로 제한됩니다.<br>함께 결제할 수 없는 상품을 장바구니에 담으려고 하는 경우에는, 함께 구매할 수 없다는 안내 메시지가 표시됩니다.</div>', 'pgall-for-woocommerce' ),
							)
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '상품별 노출 제어', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								"id"        => "pafw-payment-method-by-product",
								"title"     => "",
								"className" => "",
								"editable"  => "true",
								"sortable"  => "true",
								"repeater"  => "true",
								"type"      => "SortableTable",
								"default"   => array(),
								"template"  => array(
									"enabled"         => "yes",
									"products"        => "",
									"payment_methods" => "",
								),
								"elements"  => array(
									array(
										"id"        => "enabled",
										"title"     => __( "활성화", "pgall-for-woocommerce" ),
										"className" => " one wide column fluid",
										"default"   => "yes",
										"type"      => "Toggle"
									),
									array(
										"id"           => "products",
										"title"        => __( "상품", "pgall-for-woocommerce" ),
										"className"    => " six wide column search fluid",
										'multiple'     => true,
										'search'       => true,
										'disableClear' => true,
										"type"         => "SearchSelect",
										"default"      => "",
										'action'       => pafw_get_default_language_args() . 'action=' . PAFW()->slug() . '-target_search&type=product',
										"placeholder"  => __( "상품을 선택하세요.", "pgall-for-woocommerce" )
									),
									array(
										"id"          => "payment_methods",
										"title"       => __( "결제수단", "pgall-for-woocommerce" ),
										"className"   => " seven wide column search fluid",
										'multiple'    => true,
										'search'      => true,
										"type"        => "Select",
										"default"     => "",
										'options'     => self::get_payment_gateways(),
										"placeholder" => __( "결제수단을 선택하세요.", "pgall-for-woocommerce" )
									)
								)
							)
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '카테고리별 노출 제어', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								"id"        => "pafw-payment-method-by-category",
								"title"     => "",
								"className" => "",
								"editable"  => "true",
								"sortable"  => "true",
								"repeater"  => "true",
								"type"      => "SortableTable",
								"default"   => array(),
								"template"  => array(
									"enabled"         => "yes",
									"categories"      => "",
									"payment_methods" => "",
								),
								"elements"  => array(
									array(
										"id"        => "enabled",
										"title"     => __( "활성화", "pgall-for-woocommerce" ),
										"className" => " one wide column fluid",
										"default"   => "yes",
										"type"      => "Toggle"
									),
									array(
										"id"           => "categories",
										"title"        => __( "상품 카테고리", "pgall-for-woocommerce" ),
										"className"    => " six wide column search fluid",
										'multiple'     => true,
										'search'       => true,
										'disableClear' => true,
										"type"         => "SearchSelect",
										"default"      => "",
										'action'       => pafw_get_default_language_args() . 'action=' . PAFW()->slug() . '-target_search&type=category',
										"placeholder"  => __( "카테고리를 선택하세요.", "pgall-for-woocommerce" )
									),
									array(
										"id"          => "payment_methods",
										"title"       => __( "결제수단", "pgall-for-woocommerce" ),
										"className"   => " seven wide column search fluid",
										'multiple'    => true,
										'search'      => true,
										"type"        => "Select",
										"default"     => "",
										'options'     => self::get_payment_gateways(),
										"placeholder" => __( "결제수단을 선택하세요.", "pgall-for-woocommerce" )
									)
								)
							)
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '상품 속성별 노출 제어', 'pgall-for-woocommerce' ),
						'elements' => array(
							array(
								"id"        => "pafw-payment-method-by-attributes",
								"title"     => "",
								"className" => "",
								"editable"  => "true",
								"sortable"  => "true",
								"repeater"  => "true",
								"type"      => "SortableTable",
								"default"   => array(),
								"template"  => array(
									"enabled"         => "yes",
									"categories"      => "",
									"payment_methods" => "",
								),
								"elements"  => array(
									array(
										"id"        => "enabled",
										"title"     => __( "활성화", "pgall-for-woocommerce" ),
										"className" => " one wide column fluid",
										"default"   => "yes",
										"type"      => "Toggle"
									),
									array(
										"id"           => "attributes",
										"title"        => __( "상품 속성", "pgall-for-woocommerce" ),
										"className"    => " six wide column search fluid",
										'multiple'     => true,
										'search'       => true,
										'disableClear' => true,
										"type"         => "SearchSelect",
										"default"      => "",
										'action'       => pafw_get_default_language_args() . 'action=' . PAFW()->slug() . '-target_search&type=attributes',
										"placeholder"  => __( "상품 속성을 선택하세요.", "pgall-for-woocommerce" )
									),
									array(
										"id"          => "payment_methods",
										"title"       => __( "결제수단", "pgall-for-woocommerce" ),
										"className"   => " seven wide column search fluid",
										'multiple'    => true,
										'search'      => true,
										"type"        => "Select",
										"default"     => "",
										'options'     => self::get_payment_gateways(),
										"placeholder" => __( "결제수단을 선택하세요.", "pgall-for-woocommerce" )
									)
								)
							)
						)
					),
					array(
						'type'     => 'Section',
						'title'    => __( '사이트 언어별 노출 제어', 'pgall-for-woocommerce' ),
						'elements' => self::get_language_control_setting()
					)
				)
			);
		}

		static function enqueue_scripts() {
			wp_enqueue_style( 'mshop-setting-manager', PAFW()->plugin_url() . '/includes/admin/setting-manager/css/setting-manager.min.css' );
			wp_enqueue_script( 'mshop-setting-manager', PAFW()->plugin_url() . '/includes/admin/setting-manager/js/setting-manager.min.js', array(
				'underscore',
				'jquery',
				'jquery-ui-core'
			) );
		}

		public static function output() {
			$settings = self::get_basic_setting();

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array(
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => PAFW()->slug() . '-update_pafw_payment_method_control_settings',
				'settings' => $settings,
				'slug'     => PAFW()->slug()
			) );

			PAFW_SettingHelper::get_settings( $settings );

			?>
            <script>
                jQuery(document).ready(function () {
                    jQuery(this).trigger('mshop-setting-manager', ['mshop-setting-wrapper', '100', <?php echo json_encode( PAFW_SettingHelper::get_settings( $settings ) ); ?>, null, null]);
                });
            </script>

            <div id="mshop-setting-wrapper"></div>
			<?php
		}

	}
endif;



