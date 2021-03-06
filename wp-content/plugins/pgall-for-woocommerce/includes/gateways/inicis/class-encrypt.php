<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//PHP 5.3 User hex2bin function support
if ( ! function_exists( 'hex2bin' ) ) {
	define( 'HEX2BIN_WS', " \t\n\r" );
	function hex2bin( $hex_string ) {
		$pos    = 0;
		$result = '';
		while ( $pos < strlen( $hex_string ) ) {
			if ( strpos( HEX2BIN_WS, $hex_string{$pos} ) !== false ) {
				$pos ++;
			} else {
				$code   = hexdec( substr( $hex_string, $pos, 2 ) );
				$pos    = $pos + 2;
				$result .= chr( $code );
			}
		}

		return $result;
	}
}

if ( ! function_exists( 'aes128_cbc_encrypt' ) ) {
	function aes128_cbc_encrypt( $key, $data, $iv ) {
		if ( function_exists( 'mcrypt_encrypt' ) ) {
			if ( 16 !== strlen( $key ) ) {
				$key = hash( 'MD5', $key, true );
			}
			if ( 16 !== strlen( $iv ) ) {
				$iv = hash( 'MD5', $iv, true );
			}
			$padding = 16 - ( strlen( $data ) % 16 );
			$data    .= str_repeat( chr( $padding ), $padding );

			return bin2hex( mcrypt_encrypt( MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv ) );
		} else {
			$ivSize = openssl_cipher_iv_length( 'AES-128-CBC' );
			$iv     = openssl_random_pseudo_bytes( $ivSize );

			$encrypted = openssl_encrypt( $data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv );

			$encrypted = bin2hex( $iv . $encrypted );

			return $encrypted;
		}
	}
}

if ( ! function_exists( 'aes256_cbc_encrypt' ) ) {
	function aes256_cbc_encrypt( $key, $data, $iv ) {
		if ( function_exists( 'mcrypt_encrypt' ) ) {
			if ( 32 !== strlen( $key ) ) {
				$key = hash( 'SHA256', $key, true );
			}
			if ( 16 !== strlen( $iv ) ) {
				$iv = hash( 'MD5', $iv, true );
			}
			$padding = 16 - ( strlen( $data ) % 16 );
			$data    .= str_repeat( chr( $padding ), $padding );

			return bin2hex( mcrypt_encrypt( MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv ) );
		} else {
			$ivSize = openssl_cipher_iv_length( 'AES-256-CBC' );
			$iv     = openssl_random_pseudo_bytes( $ivSize );

			$encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

			$encrypted = bin2hex( $iv . $encrypted );

			return $encrypted;
		}
	}
}

if ( ! function_exists( 'aes128_cbc_decrypt' ) ) {
	function aes128_cbc_decrypt( $key, $data, $iv ) {
		if ( function_exists( 'mcrypt_encrypt' ) ) {
			if ( 16 !== strlen( $key ) ) {
				$key = hash( 'MD5', $key, true );
			}
			if ( 16 !== strlen( $iv ) ) {
				$iv = hash( 'MD5', $iv, true );
			}
			$data    = mcrypt_decrypt( MCRYPT_RIJNDAEL_128, $key, hex2bin( $data ), MCRYPT_MODE_CBC, $iv );
			$padding = ord( $data[ strlen( $data ) - 1 ] );

			return substr( $data, 0, - $padding );
		} else {
			$data   = hex2bin( $data );
			$ivSize = openssl_cipher_iv_length( 'AES-128-CBC' );
			$iv     = substr( $data, 0, $ivSize );
			$data   = openssl_decrypt( substr( $data, $ivSize ), 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv );

			return $data;
		}
	}
}

if ( ! function_exists( 'aes256_cbc_decrypt' ) ) {
	function aes256_cbc_decrypt( $key, $data, $iv ) {
		if ( function_exists( 'mcrypt_encrypt' ) ) {
			if ( 32 !== strlen( $key ) ) {
				$key = hash( 'SHA256', $key, true );
			}
			if ( 16 !== strlen( $iv ) ) {
				$iv = hash( 'MD5', $iv, true );
			}
			$data    = mcrypt_decrypt( MCRYPT_RIJNDAEL_128, $key, hex2bin( $data ), MCRYPT_MODE_CBC, $iv );
			$padding = ord( $data[ strlen( $data ) - 1 ] );

			return substr( $data, 0, - $padding );
		} else {
			$data   = hex2bin( $data );
			$ivSize = openssl_cipher_iv_length( 'AES-256-CBC' );
			$iv     = substr( $data, 0, $ivSize );
			$data   = openssl_decrypt( substr( $data, $ivSize ), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

			return $data;
		}
	}
}