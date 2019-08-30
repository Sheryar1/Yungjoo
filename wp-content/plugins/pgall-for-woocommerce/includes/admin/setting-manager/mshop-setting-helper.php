<?php

require_once( ABSPATH . 'wp-admin/includes/user.php' );
if ( ! class_exists( 'PAFW_SettingHelper' ) ) {
	Class PAFW_SettingHelper {
		public static function get_settings( $setting, $postid = null ) {
			$values = array ();

			if ( ! empty( $setting['id'] ) && ! in_array( $setting['type'], array ( 'Tab', 'Page' ) ) ) {
				if ( ! empty( $setting['readonly'] ) && 'yes' == $setting['readonly'] ) {
					$value = pafw_get( $setting, 'default' );
				} else {
					$value = $postid ? get_post_meta( $postid, $setting['id'], true ) : get_option( $setting['id'], isset( $setting['default'] ) ? $setting['default'] : '' );
					if ( empty( $value ) && isset( $setting['default'] ) ) {
						$value = $setting['default'];
					}
				}

				$values[ $setting['id'] ] = apply_filters( 'msshelper_get_' . $setting['id'], $value );
			}

			if ( ! empty( $setting['elements'] ) && empty( $setting['repeater'] ) ) {
				foreach ( $setting['elements'] as $element ) {
					$values = array_merge( $values, self::get_settings( $element, $postid ) );
				}
			}

			return $values;
		}

		public static function update_settings( $setting, $postid = null ) {
			if ( ! empty( $setting['id'] ) ) {
				if ( ! empty( $setting['readonly'] ) && 'yes' == $setting['readonly'] ) {
					// this element is readonly... skip...
				} else if ( has_action( 'update_' . $setting['id'] ) ) {
					do_action( 'update_' . $setting['id'] );
				} else {
					if ( ! empty( $_REQUEST[ $setting['id'] ] ) ) {
						$postid ? update_post_meta( $postid, $setting['id'], $_REQUEST[ $setting['id'] ] ) : update_option( $setting['id'], $_REQUEST[ $setting['id'] ] );
					} else {
						$postid ? delete_post_meta( $postid, $setting['id'] ) : delete_option( $setting['id'] );
					}
				}
			}

			if ( ! empty( $setting['elements'] ) && empty( $setting['repeater'] ) ) {
				foreach ( $setting['elements'] as $element ) {
					self::update_settings( $element, $postid );
				}
			}
		}
		public static function get_setting_values( $setting ) {
			$values = array ();

			if ( ! empty( $setting['id'] ) ) {
				if ( ! empty( $setting['readonly'] ) && 'yes' == $setting['readonly'] ) {
					// this element is readonly... skip...
				} else if ( ! empty( $_REQUEST[ $setting['id'] ] ) ) {
					$values[ $setting['id'] ] = $_REQUEST[ $setting['id'] ];
				} else if( isset( $setting['default'] )  ) {
					$values[ $setting['id'] ]  = $setting['default'];
				}
			}

			if ( ! empty( $setting['elements'] ) && empty( $setting['repeater'] ) ) {
				foreach ( $setting['elements'] as $element ) {
					$values = array_merge( $values, self::get_setting_values( $element ) );
				}
			}

			return $values;
		}

		public static function filter_setting_values( $setting, $setting_values ) {
			$values = array ();

			if ( ! empty( $setting['id'] ) ) {
				if ( ! empty( $setting['readonly'] ) && 'yes' == $setting['readonly'] ) {
					// this element is readonly... skip...
				} else if ( ! empty( $setting_values[ $setting['id'] ] ) ) {
					$values[ $setting['id'] ] = $setting_values[ $setting['id'] ];
				} else if( isset( $setting['default'] )  ) {
					$values[ $setting['id'] ]  = $setting['default'];
				}
			}

			if ( ! empty( $setting['elements'] ) && empty( $setting['repeater'] ) ) {
				foreach ( $setting['elements'] as $element ) {
					$values = array_merge( $values, self::filter_setting_values( $element, $setting_values ) );
				}
			}

			return $values;
		}

	}
}