<?php
class ActionScheduler_Compatibility {
	public static function convert_hr_to_bytes( $value ) {
		if ( function_exists( 'wp_convert_hr_to_bytes' ) ) {
			return wp_convert_hr_to_bytes( $value );
		}

		$value = strtolower( trim( $value ) );
		$bytes = (int) $value;

		if ( false !== strpos( $value, 'g' ) ) {
			$bytes *= GB_IN_BYTES;
		} elseif ( false !== strpos( $value, 'm' ) ) {
			$bytes *= MB_IN_BYTES;
		} elseif ( false !== strpos( $value, 'k' ) ) {
			$bytes *= KB_IN_BYTES;
		}

		// Deal with large (float) values which run into the maximum integer size.
		return min( $bytes, PHP_INT_MAX );
	}
	public static function raise_memory_limit() {
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			return wp_raise_memory_limit( 'admin' );
		}

		$current_limit     = @ini_get( 'memory_limit' );
		$current_limit_int = self::convert_hr_to_bytes( $current_limit );

		if ( -1 === $current_limit_int ) {
			return false;
		}

		$wp_max_limit       = WP_MAX_MEMORY_LIMIT;
		$wp_max_limit_int   = self::convert_hr_to_bytes( $wp_max_limit );
		$filtered_limit     = apply_filters( 'admin_memory_limit', $wp_max_limit );
		$filtered_limit_int = self::convert_hr_to_bytes( $filtered_limit );

		if ( -1 === $filtered_limit_int || ( $filtered_limit_int > $wp_max_limit_int && $filtered_limit_int > $current_limit_int ) ) {
			if ( false !== @ini_set( 'memory_limit', $filtered_limit ) ) {
				return $filtered_limit;
			} else {
				return false;
			}
		} elseif ( -1 === $wp_max_limit_int || $wp_max_limit_int > $current_limit_int ) {
			if ( false !== @ini_set( 'memory_limit', $wp_max_limit ) ) {
				return $wp_max_limit;
			} else {
				return false;
			}
		}
		return false;
	}
}
