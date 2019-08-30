<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Admin_Dashboard' ) ) :
	class PAFW_Admin_Dashboard {
		public function __construct() {
			if ( current_user_can( 'manage_options' ) ) {
				add_action( 'wp_dashboard_setup', array ( $this, 'init' ), 1 );
			}
		}
		public function init() {
			wp_add_dashboard_widget( 'mnp_dashboard_notices', __( '쇼핑몰 결제 PGALL 알림 서비스', 'pgall-for-woocommerce' ), array (
				$this,
				'notices'
			) );
		}
		public function notices() {
			wp_enqueue_script( 'pafw-admin-dashboard', PAFW()->plugin_url() . '/assets/js/admin-dashboard.js', array ( 'jquery', 'jquery-blockui' ), PAFW_VERSION );
			wp_localize_script( 'pafw-admin-dashboard', 'pafw_admin_dashboard', array (
				'url' => 'https://pgall.co.kr/msb-get-posts/?bid=plugin-notices&mbcat=mshop-common,' . PAFW()->slug()
			) );

			?>
            <div class="pafw-notices">
            </div>
			<?php
		}

	}

endif;

return new PAFW_Admin_Dashboard();
