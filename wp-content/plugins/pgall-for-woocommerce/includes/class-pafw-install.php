<?php

defined( 'ABSPATH' ) || exit;
class PAFW_Install {
	public static function init() {
		add_action( 'init', array ( __CLASS__, 'check_version' ), 5 );
	}
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'pafw_db_version' ), PAFW_VERSION, '<' ) ) {
			self::install();
		}
	}
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'pafw_installing' ) ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'pafw_installing', 'yes', MINUTE_IN_SECONDS * 10 );

		self::create_tables();
		self::install_key_files();
		self::update_db_version();

		delete_transient( 'pafw_installing' );
	}
	public static function update_db_version( $version = null ) {
		delete_option( 'pafw_db_version' );
		add_option( 'pafw_db_version', is_null( $version ) ? PAFW_VERSION : $version );
	}

	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'pafw_transaction';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
			$sql = "CREATE TABLE `$table_name` (
                      `id` bigint(20) NOT NULL AUTO_INCREMENT,
                      `date` datetime NOT NULL,
                      `payment_method` varchar(50) NOT NULL,
                      `payment_method_title` varchar(50) NOT NULL,
                      `device_type` varchar(10) NOT NULL,
                      `order_id` bigint(20) DEFAULT NULL,
                      `order_total` float DEFAULT NULL,
                      `user_id` bigint(20) DEFAULT NULL,
                      `result_code` int(11) DEFAULT NULL,
                      `result_message` varchar(1000) DEFAULT NULL,
                      `error_code` varchar(20) DEFAULT NULL,
                      PRIMARY KEY (`id`),
                      KEY `s1` (`date`,`payment_method`,`result_code`,`device_type`) USING BTREE
                    ) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}

		$table_name = $wpdb->prefix . 'pafw_statistics';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
			$sql = "CREATE TABLE `$table_name` (
					  `id` bigint(20) NOT NULL AUTO_INCREMENT,
					  `payment_method` varchar(100) DEFAULT NULL,
					  `payment_method_title` varchar(100) DEFAULT NULL,
					  `merchant_id` varchar(100) DEFAULT NULL,
					  `amount` float DEFAULT NULL,
					  `currency` varchar(100) DEFAULT NULL,
					  `date` datetime,
					  PRIMARY KEY (`id`)
					) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}

	private static function unzip( $archive, $target_path ) {
		if ( class_exists( 'ZipArchive' ) ) {
			$zip = new ZipArchive();
			if ( $zip->open( $archive ) === true ) {
				$zip->extractTo( $target_path );
				$zip->close();

				return true;
			} else {
				return false;
			}
		}
	}
	private static function install_key_files() {
		try {
			if ( ! file_exists( WP_CONTENT_DIR . '/inicis' ) ) {
				$old = umask( 0 );
				mkdir( WP_CONTENT_DIR . '/inicis', 0755, true );
				umask( $old );

				if ( file_exists( PAFW()->plugin_path() . '/lib/inicis/inipay.zip' ) ) {
					self::unzip( PAFW()->plugin_path() . '/lib/inicis/inipay.zip', WP_CONTENT_DIR . '/inicis' );
				}
			}
		} catch ( Exception $e ) {

		}
	}
}

PAFW_Install::init();
