<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'PAFW_Admin' ) ) :

	class PAFW_Admin {

		static function init() {
			add_action( 'admin_menu', 'PAFW_Admin::admin_menu' );
			add_action( 'add_meta_boxes_shop_order', array ( 'PAFW_Admin', 'add_meta_boxes' ), 10, 2 );
			add_action( 'add_meta_boxes_shop_subscription', array ( 'PAFW_Admin', 'add_meta_boxes' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array ( 'PAFW_Admin', 'admin_enqueue_scripts' ) );
			add_action( 'admin_notices', array ( 'PAFW_Admin', 'admin_notices' ) );
		}

		static function admin_notices() {
			if( ! is_ssl() ) {
				?>
				<div class="notice notice-error">
					<p><a href="https://www.pgall.co.kr/n-20180419/" target="_blank">[중요] IOS 11.3 결제 오류 방지를 위한 위한 보안서버 (SSL) 적용 안내</a></p>
				</div>
				<?php
			}
		}

		static function add_meta_boxes( $post ) {
			$order = wc_get_order( $post );

			$payment_method  = pafw_get_object_property( $order, 'payment_method' );
			$payment_gateway = pafw_get_payment_gateway( $payment_method );

			if ( $payment_gateway && $payment_gateway instanceof PAFW_Payment_Gateway ) {
				$payment_gateway->add_meta_boxes( $order );
			}
		}

		static function admin_menu() {
			add_menu_page( __( 'PGALL', 'pgall-for-woocommerce' ), __( 'PGALL', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_setting', '', PAFW()->plugin_url() . '/assets/images/mshop-icon.png', '20.876503947292' );
			add_submenu_page( 'pafw_setting', __( '결제설정', 'pgall-for-woocommerce' ), __( '결제설정', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_setting', 'PAFW_Admin_Settings::output' );
			add_submenu_page( 'pafw_setting', __( '리뷰설정', 'pgall-for-woocommerce' ), __( '리뷰설정', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_review_setting', 'PAFW_Admin_Review_Settings::output' );
			add_submenu_page( 'pafw_setting', __( '결제수단 노출제어', 'pgall-for-woocommerce' ), __( '결제수단 노출제어', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_payment_method_setting', 'PAFW_Admin_Payment_Method_Control_Settings::output' );
			add_submenu_page( 'pafw_setting', __( '매출통계', 'pgall-for-woocommerce' ), __( '매출통계', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_sales_statistics', 'PAFW_Admin::sales_statistics' );
			add_submenu_page( 'pafw_setting', __( '결제통계', 'pgall-for-woocommerce' ), __( '결제통계', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_health_status', 'PAFW_Admin::payment_statistics' );
			add_submenu_page( 'pafw_setting', __( '온라인 가입신청', 'pgall-for-woocommerce' ), __( '온라인 가입신청', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_apply_service', '' );
			add_submenu_page( 'pafw_setting', __( '도구', 'pgall-for-woocommerce' ), __( '도구', 'pgall-for-woocommerce' ), 'manage_woocommerce', 'pafw_tools', 'PAFW_Admin_Tools::output' );
		}

		static function sales_statistics() {
			if ( 0 == count( PAFW()->get_enabled_payment_gateways() ) ) {
				include( 'views/guide.php' );
			} else {
				include( 'views/sales-statistics.php' );
			}
		}

		static function payment_statistics() {
			if ( 0 == count( PAFW()->get_enabled_payment_gateways() ) ) {
				include( 'views/guide.php' );
			} else {
				include( 'views/payment-statistics.php' );
			}
		}

		static function admin_enqueue_scripts() {
			wp_enqueue_script( 'pafw-admin-menu', PAFW()->plugin_url() . '/assets/js/admin/admin-menu.js', array ( 'jquery' ), PAFW_VERSION );
			wp_localize_script( 'pafw-admin-menu', '_pafw_admin_menu', array (
				'apply_service_url' => 'https://www.pgall.co.kr/apply-online/'
			) );
		}
	}

	PAFW_Admin::init();

endif;
