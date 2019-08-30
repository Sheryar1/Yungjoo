<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class PAFW_KakaoPay_API {

	const CMD_READY       = 'ready';
	const ORDER_CANCEL    = 'order_cancel';
	const RETURN_REGISTER = 'return_register';
	const RETURN_CANCEL   = 'return_cancel';

	public static function default_args() {
		return array (
			'version' => '1.0'
		);
	}

	public static function call( $command, $args = array () ) {
		$api_url = 'https://kakaopay-api.codemshop.com/';

		$args = array_merge( $args, array ( 'command' => $command ), self::default_args() );

		$response = wp_remote_post( $api_url, array (
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array (),
			'body'        => $args,
			'cookies'     => array ()
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			$response = json_decode( $response['body'], true );

			if ( $response['result'] ) {
				return $response['data'];
			} else {
				return new WP_Error( $response->result, $response->notice );
			}
		}
	}

	public static function make_api_url( $action, $url ) {
		$api_url = 'https://kakaopay-api.codemshop.com/?';

		$params = array (
			'command'      => $action,
			'version'      => '1.0',
			'redirect_url' => $url
		);

		return $api_url . http_build_query( $params );
	}
}
