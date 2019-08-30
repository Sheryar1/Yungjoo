<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Admin_Review_Settings' ) ) :

	class PAFW_Admin_Review_Settings {

		static $order_statuses = null;

		static function update_settings() {
			$_REQUEST = array_merge( $_REQUEST, json_decode( stripslashes( $_REQUEST['values'] ), true ) );

			PAFW_SettingHelper::update_settings( self::get_basic_setting() );

			wp_send_json_success();
		}

		static function get_basic_setting() {
			return array (
				'type'     => 'Page',
				'class'    => 'active',
				'title'    => __( '기본 설정', 'pgall-for-woocommerce' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( '리뷰 자동 등록 기능', 'pgall-for-woocommerce' ),
						'elements' => array (
							array (
								"id"        => "pafw-use-smart-review",
								"title"     => "활성화",
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">리뷰 자동 등록 기능을 사용합니다.</div>', 'pgall-for-woocommerce' )
							),
							array (
								"id"          => "pafw-smart-review-template",
								"showIf"      => array ( 'pafw-use-smart-review' => 'yes' ),
								"className"   => "",
								"title"       => __( "리뷰 아이콘", 'pgall-for-woocommerce' ),
								"type"        => "Select",
								'default'     => 'type1',
								"placeholder" => __( '리뷰 템플릿을 선택하세요.', 'pgall-for-woocommerce' ),
								"options"     => apply_filters( 'pafw-review-template', array (
									'type1' => "Smile",
									'type2' => "Rating Black",
									'type3' => "Rating White",
									'type4' => "Count",
								) ),
							),
							array (
								"id"        => "pafw-smart-review-rate",
								"showIf"    => array ( 'pafw-use-smart-review' => 'yes' ),
								"title"     => "리뷰 평점",
								"className" => "",
								"editable"  => "true",
								"sortable"  => "true",
								"repeater"  => "true",
								"type"      => "SortableTable",
								"default"   => PAFW_Review::get_default_review_contents(),
								"elements"  => array (
									array (
										"id"        => "default",
										"title"     => __( "기본", "pgall-for-woocommerce" ),
										"className" => " one wide column fluid",
										"type"      => "Toggle",
										'tooltip'   => array (
											'title' => array (
												'title'   => __( '주의', 'pgall-for-woocommerce' ),
												'content' => __( '기본은 1개만 ON 선택 하세요.', 'pgall-for-woocommerce' ),
											)
										)
									),
									array (
										"id"          => "rate",
										"title"       => __( "평점", "pgall-for-woocommerce" ),
										"className"   => " one wide column fluid",
										"type"        => "Text",
										"default"     => "",
										"placeholder" => __( "평점을 입력하세요.", "pgall-for-woocommerce" )
									),
									array (
										"id"          => "label",
										"className"   => " five wide column fluid",
										"title"       => __( "레이블", "pgall-for-woocommerce" ),
										"type"        => "Text",
										"default"     => "",
										"placeholder" => __( "평점 레이블을 입력하세요.", "pgall-for-woocommerce" )
									),
									array (
										"id"          => "content",
										"className"   => " eight wide column fluid",
										"title"       => __( "리뷰 내용", 'pgall-for-woocommerce' ),
										"type"        => "TextArea",
										'default'     => '',
										"placeholder" => get_option( "pafw-smart-review-placeholder", __( '리뷰를 작성 해 주세요.', 'pgall-for-woocommerce' ) )
									)
								)
							),
							array (
								"id"        => "pafw-smart-review-placeholder",
								"showIf"    => array ( 'pafw-use-smart-review' => 'yes' ),
								"title"     => "리뷰작성 안내문구",
								"className" => "fluid",
								"type"      => "Text",
								"default"   => __( '리뷰를 작성 해 주세요.', 'pgall-for-woocommerce' )
							),
							array (
								"id"        => "pafw-user-can-edit-comment",
								"showIf"    => array ( 'pafw-use-smart-review' => 'yes' ),
								"title"     => "리뷰 작성",
								"className" => "",
								"type"      => "Toggle",
								"default"   => "no",
								"desc"      => __( '<div class="desc2">구매자가 리뷰 내용을 직접 작성 할 수 있습니다.</div>', 'pgall-for-woocommerce' )
							),
						)
					)
				)
			);
		}

		static function get_wc_review_disabled_notification() {

			if ( class_exists( 'MSHOP_MCommerce_Premium' ) || class_exists( 'MC_MShop' ) ) {
				$setting_url = admin_url( '/admin.php?page=mshop_general&tab=products' );
			} else {
				$setting_url = admin_url( '/admin.php?page=wc-settings&tab=products' );
			}

			return array (
				'type'     => 'Page',
				'class'    => 'active',
				'title'    => __( '기본 설정', 'pgall-for-woocommerce' ),
				'elements' => array (
					array (
						'type'     => 'Section',
						'title'    => __( '리뷰 자동 등록 기능', 'pgall-for-woocommerce' ),
						'elements' => array (
							array (
								'id'       => 'guide',
								'type'     => 'Label',
								'readonly' => 'yes',
								'default'  => sprintf( __( '<p style="margin: 10px 5px;">리뷰 자동 등록 기능은 <a href="%s" target="_blank">우커머스 상품평 기능</a>을 활성화하신 후 이용하실 수 있습니다.</p>', 'pgall-for-woocommerce' ), $setting_url )
							)
						)
					)
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
			if ( 'yes' === get_option( 'woocommerce_enable_reviews', 'yes' ) ) {
				$settings = self::get_basic_setting();
			} else {
				$settings = self::get_wc_review_disabled_notification();
			}

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array (
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => PAFW()->slug() . '-update_pafw_review_settings',
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



