<?php


//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Session' ) ) {

	class PAFW_Session {

		const ERR_REQUEST_PAYMENT = - 1;
		const ERR_SUCCESS = 0;
		const ERR_CANCEL = 1;
		const ERR_FAIL = 2;

		const ERROR_CANCEL_BY_USER = '1001';
		const ERR_BROWSER_REFRESH_OR_CLOSED = '1002';

		const PAYMENT_SESSION_KEY = 'pafw_payment_session';
		public static function process_payment( $order ) {
			self::init_session( $order );
		}

		public static function thankyou_page( $order ) {
			self::update_payment_result( $order );
		}

		public static function payment_cancel( $order ) {
			self::maybe_update_session( self::ERR_CANCEL, __( '사용자 취소', 'pgall-for-woocommerce' ), self::ERROR_CANCEL_BY_USER );
		}

		public static function payment_fail( $order, $error_code, $result_message ) {
			if ( ! self::maybe_update_session( self::ERR_FAIL, $result_message, $error_code ) && $order ) {
				global $wpdb;
				$order_id       = pafw_get_object_property( $order, 'id' );
				$payment_method = pafw_get_object_property( $order, 'payment_method' );

				$session_id = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}pafw_transaction WHERE order_id = {$order_id} AND payment_method = '{$payment_method}' ORDER BY id DESC LIMIT 1" );

				if ( $session_id ) {
					self::update_session( $session_id, self::ERR_FAIL, $result_message, $error_code );
				}
			}
		}

		public static function clear_session( $update = true ) {
			if ( $update && self::ERR_REQUEST_PAYMENT == self::get_current_session_status() ) {
				self::maybe_update_session( self::ERR_CANCEL, __( '브라우저 새로고침 또는 종료에 따른 취소', 'pgall-for-woocommerce' ), self::ERR_BROWSER_REFRESH_OR_CLOSED );
			}

			$expire = time() + intval( apply_filters( 'pafw_session_expiration', 60 * 60 * 48 ) ); // 48 Hours.;
			setcookie( PAFW_Session::PAYMENT_SESSION_KEY, '', $expire, '/', COOKIE_DOMAIN );
		}
		public static function init_session( $order ) {

			self::clear_session();

			$session_key = self::generate_unique_session_key();
			$expire      = time() + intval( apply_filters( 'pafw_session_expiration', 60 * 60 * 48 ) ); // 48 Hours.;

			$session_id = self::save_session_to_table( $order );

			if ( $session_id ) {
				setcookie( PAFW_Session::PAYMENT_SESSION_KEY, $session_key . '|' . $session_id, $expire, '/', COOKIE_DOMAIN );
			}
		}

		protected static function get_current_session_key() {
			if ( isset( $_COOKIE[ PAFW_Session::PAYMENT_SESSION_KEY ] ) ) {
				list( $session_key, $session_id ) = explode( '|', $_COOKIE[ PAFW_Session::PAYMENT_SESSION_KEY ] );

				return $session_key;
			}

			return null;
		}

		protected static function get_current_session_id() {
			if ( isset( $_COOKIE[ PAFW_Session::PAYMENT_SESSION_KEY ] ) ) {
				list( $session_key, $session_id ) = explode( '|', $_COOKIE[ PAFW_Session::PAYMENT_SESSION_KEY ] );

				return $session_id;
			}

			return null;
		}

		public static function update_payment_result( $order ) {
			if ( ! self::maybe_update_session( self::ERR_SUCCESS, __( '결제성공', 'pgall-for-woocommerce' ) ) ) {
				global $wpdb;
				$order_id       = pafw_get_object_property( $order, 'id' );
				$payment_method = pafw_get_object_property( $order, 'payment_method' );

				$session_id = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}pafw_transaction WHERE order_id = {$order_id} AND payment_method = '{$payment_method}' ORDER BY id DESC LIMIT 1" );

				if ( $session_id ) {
					self::update_session( $session_id, self::ERR_SUCCESS, __( '결제성공', 'pgall-for-woocommerce' ) );
				}
			}
		}
		public static function maybe_update_session( $result_code, $result_message, $error_code = '' ) {
			$session_key = self::get_current_session_key();

			if ( $session_key ) {
				self::update_session( self::get_current_session_id(), $result_code, $result_message, $error_code );
				self::clear_session( false );

				return true;
			}

			return false;
		}

		public static function get_current_session_status() {
			return self::get_session_status( self::get_current_session_id() );
		}
		public static function get_session_status( $session_id ) {
			global $wpdb;

			if ( ! is_null( $session_id ) ) {
				return $wpdb->get_var( "SELECT result_code FROM {$wpdb->prefix}pafw_transaction WHERE id = $session_id" );
			}

			return null;
		}
		public static function update_session( $session_id, $result_code = 10, $result_message = '사용자 취소', $error_code = '' ) {
			global $wpdb;

			$wpdb->update(
				$wpdb->prefix . 'pafw_transaction',
				array (
					'result_code'    => $result_code,
					'result_message' => $result_message,
					'error_code'     => $error_code
				),
				array (
					'id' => $session_id
				),
				array ( '%d', '%s', '%s' ),
				array ( '%d' )
			);
		}

		protected static function generate_unique_session_key() {
			require_once( ABSPATH . 'wp-includes/class-phpass.php' );
			$hasher = new PasswordHash( 8, false );

			return md5( $hasher->get_random_bytes( 32 ) );
		}
		protected static function save_session_to_table( $order ) {
			global $wpdb;

			if ( $order ) {
				$wpdb->insert(
					$wpdb->prefix . 'pafw_transaction',
					array (
						'date'                 => current_time( 'mysql' ),
						'payment_method'       => pafw_get_object_property( $order, 'payment_method' ),
						'payment_method_title' => pafw_get_object_property( $order, 'payment_method_title' ),
						'device_type'          => wp_is_mobile() ? '모바일' : 'PC',
						'order_id'             => pafw_get_object_property( $order, 'id' ),
						'order_total'          => $order->get_total(),
						'user_id'              => get_current_user_id(),
						'result_code'          => self::ERR_REQUEST_PAYMENT,
						'result_message'       => __( '결제시도', 'pgall-for-woocommerce' ),
						'error_code'           => ''
					),
					array (
						'%s',
						'%s',
						'%s',
						'%s',
						'%d',
						'%d',
						'%d',
						'%s',
						'%s',
						'%s'
					)
				);

				return $wpdb->insert_id;
			}

			return null;
		}

		public static function cancel_unfinished_payment_request() {
			global $wpdb;

			$date = date( "Y-m-d H:i:s", strtotime( '-30 MINUTES', current_time( 'timestamp' ) ) );

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}pafw_transaction 
                     SET 
                     	result_code = %d 
                     	AND result_message = %s 
                     	AND error_code = '1002' 
                     WHERE 
                     	result_code = -1 
                     	AND date < %s", self::ERR_BROWSER_REFRESH_OR_CLOSED, __( '브라우저 새로고침 또는 종료에 따른 취소', 'pgall-for-woocommerce' ), $date
				)
			);

		}

	}

}