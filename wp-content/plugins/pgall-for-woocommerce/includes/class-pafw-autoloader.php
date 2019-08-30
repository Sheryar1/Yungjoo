<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class PAFW_Autoloader {
	private $include_path = '';
	public function __construct() {
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
		}

		spl_autoload_register( array ( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( plugin_dir_path( PAFW_PLUGIN_FILE ) ) . '/includes/';
	}
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once( $path );

			return true;
		}

		return false;
	}
	public function autoload( $class ) {
		static $gateways = array ( 'pafw', 'inicis', 'nicepay', 'kcp', 'lguplus', 'payco', 'kakaopay' );

		$class = strtolower( $class );

		if ( strpos( $class, 'pafw_' ) === false && strpos( $class, 'wc_gateway_' ) === false && strpos( $class, 'mfd_' ) === false ) {
			return;
		}

		$file = $this->get_file_name_from_class( $class );
		$path = '';

		if ( strpos( $class, 'pafw_admin' ) === 0 ) {
			$path = $this->include_path . 'admin/';
		}

		$paths = explode( '_', $class );

		if ( strpos( $class, 'pafw_settings' ) === 0 && count( $paths ) > 2 ) {
			$path = $this->include_path . 'admin/settings/' . $paths[2] . '/';
		} else if ( strpos( $class, 'pafw_email_' ) === 0 ) {
			$path = $this->include_path . 'emails/';
		} else if ( strpos( $class, 'pafw_meta_box' ) === 0 ) {
			$path = $this->include_path . 'admin/meta-boxes/';
		} else if ( strpos( $class, 'wc_gateway_' ) === 0 && in_array( $paths[2], $gateways ) ) {
			$path = $this->include_path . 'gateways/' . $paths[2] . '/';
		} else if ( strpos( $class, 'pafw_settinghelper' ) === 0 ) {
			$this->load_file( $this->include_path . '/admin/setting-manager/mshop-setting-helper.php' );
			return;
		} elseif ( strpos( $class, 'mfd_' ) === 0 ) {
			$path = $this->include_path . 'fields/';
		}

		if ( empty( $path ) || ( ! $this->load_file( $path . $file ) && strpos( $class, 'mshop_pg_' ) === 0 ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}

}

new PAFW_Autoloader();