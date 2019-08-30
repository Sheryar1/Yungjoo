<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Gateway' ) ) {

	class PAFW_Gateway {

		static function gateway_url() {
			return 'https://pg.codemshop.com';
		}
		static function compress_folder( $path ) {
			$zip_file   = PAFW()->plugin_path() . "/pafw.zip";
			$zipArchive = new ZipArchive();

			if ( ! $zipArchive->open( $zip_file, ZipArchive::CREATE | ZIPARCHIVE::OVERWRITE ) ) {
				throw new Exception( __( '압축 파일을 생성할 수 없습니다. 웹서버 권한 설정을 확인해주세요.', 'pgall-for-woocommerce' ) );
			}

			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $path ),
				RecursiveIteratorIterator::LEAVES_ONLY
			);

			foreach ( $files as $name => $file ) {
				// Skip directories (they would be added automatically)
				if ( ! $file->isDir() ) {
					// Get real and relative path for current file
					$filePath     = $file->getRealPath();
					$relativePath = substr( $filePath, strlen( $path ) + 1 );

					// Add current file to archive
					$zipArchive->addFile( $filePath, $relativePath );
				}
			}

			$zipArchive->close();

			$data = file_get_contents( $zip_file );

			unlink( $zip_file );

			return base64_encode( $data );
		}
		static function inicis_register_gateway() {
			$settings = array ();
			$gateways = WC()->payment_gateways()->payment_gateways();

			foreach ( $gateways as $gateway_id => $gateway ) {
				if ( 0 === strpos( $gateway_id, 'inicis' ) ) {
					$settings = $gateway->settings;
					break;
				}
			}

			if ( empty( $settings ) || empty( $settings['gateway_id'] ) ) {
				throw new Exception( '게이트웨이 아이디를 입력해주세요.' );
			}

			if ( empty( $settings['libfolder'] ) ) {
				throw new Exception( '이니페이 설치 경로를 확인해주세요.' );
			}

			$data = self::compress_folder( $settings['libfolder'] . '/key' );

			$response = wp_remote_post( self::gateway_url(), array (
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array (),
					'body'        => array (
						'service'    => 'inicis',
						'version'    => '1.0',
						'command'    => 'register',
						'domain'     => home_url(),
						'gateway_id' => $settings['gateway_id'],
						'data'       => $data
					),
					'cookies'     => array ()
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			} else {
				$result = json_decode( $response['body'], true );

				if ( '0000' == pafw_get( $result, 'code' ) ) {
					return true;
				} else {
					throw new Exception( sprintf( '[%s] %s', pafw_get( $result, 'code' ), pafw_get( $result, 'message' ) ) );
				}
			}
		}
		public static function payment_action( $action, $amount, $order, $payment_gateway ) {
			global $wpdb;

			try {
				if ( 0 == $amount ) {
					return;
				}

				$wpdb->insert(
					$wpdb->prefix . 'pafw_statistics',
					array (
						'payment_method'       => pafw_get_object_property( $order, 'payment_method' ),
						'payment_method_title' => pafw_get_object_property( $order, 'payment_method_title' ),
						'merchant_id'          => $payment_gateway->get_merchant_id(),
						'amount'               => 'completed' == $action ? $amount : - 1 * $amount,
						'currency'             => $order->get_currency(),
						'date'                 => current_time( 'mysql' ),
					),
					array (
						'%s',
						'%s',
						'%s',
						'%f',
						'%s',
						'%s',
					)
				);
			} catch ( Exception $e ) {

			}
		}

		public static function cleanup_completed_scheduled_action( $action ) {
			global $wpdb;

			$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_title = '{$action}' AND post_type = 'scheduled-action' AND post_status IN ( 'publish', 'failed', 'trash' )" );

			if ( ! empty( $ids ) ) {
				$id_params = implode( ',', $ids );
				$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID IN ({$id_params})" );
				$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ({$id_params})" );
				$wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_post_ID IN ({$id_params})" );
			}
		}

		public static function update_statistics() {
			global $wpdb;

			self::cleanup_completed_scheduled_action( 'pafw_cancel_unfinished_payment_request' );

			$items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pafw_statistics", ARRAY_A );

			if ( empty( $items ) ) {
				return;
			}

			$response = wp_remote_post( self::gateway_url(), array (
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array (),
					'body'        => array (
						'service' => 'statistics',
						'version' => '1.0',
						'command' => 'register',
						'domain'  => home_url(),
						'items'   => $items
					),
					'cookies'     => array ()
				)
			);

			if ( ! is_wp_error( $response ) ) {
				$result = json_decode( $response['body'], true );

				if ( '0000' == pafw_get( $result, 'code' ) ) {
					$ids = implode( ',', array_map( 'absint', array_column( $items, 'id' ) ) );
					$wpdb->query( "DELETE FROM {$wpdb->prefix}pafw_statistics WHERE id IN($ids)" );
				}
			}
		}
	}

}