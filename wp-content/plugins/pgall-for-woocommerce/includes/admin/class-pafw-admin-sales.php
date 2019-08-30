<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'PAFW_Admin_Sales' ) ) :

	class PAFW_Admin_Sales {

		static $default_excluded_order_status = array ( 'wc-refunded', 'wc-cancelled', 'wc-failed', 'wc-on-hold', 'wc-pending', 'wc-cancel-request', 'trash' );
		static function get_order_total_by_date( $date_from, $date_to = '', $excluded_order_statuses = null ) {
			global $wpdb;

			if ( is_null( $excluded_order_statuses ) ) {
				$excluded_order_statuses = apply_filters( 'pafw_default_excluded_order_status', self::$default_excluded_order_status );
			}

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$date_where           = "AND ( posts.post_date BETWEEN '{$date_from}' AND '{$date_to}' )";
			$order_statuses_where = "'" . implode( "','", array_diff( array_keys( wc_get_order_statuses() ), $excluded_order_statuses ) ) . "'";

			$sql = "SELECT count( posts.ID) count, SUM(postmeta.meta_value) order_total
					FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->postmeta} as postmeta ON posts.ID = postmeta.post_id
					WHERE
						postmeta.meta_key = '_order_total'
						AND posts.post_type = 'shop_order'
						AND posts.post_status IN ( {$order_statuses_where} )
						{$date_where}
					";

			return $wpdb->get_row( $sql, ARRAY_A );
		}
		static function get_summary_data() {
			$first_day_of_week = ( new DateTime() )->modify( 'last sunday' )->format( 'Y-m-d 00:00:00' );

			return array (
				'today' => self::get_order_total_by_date( date( 'Y-m-d 00:00:00' ) ),
				'week'  => self::get_order_total_by_date( $first_day_of_week ),
				'month' => self::get_order_total_by_date( date( 'Y-m-01 00:00:00' ) ),
				'year'  => self::get_order_total_by_date( date( 'Y-01-01 00:00:00' ) )
			);
		}
		static function get_daily_sales_by_date( $date_from, $date_to = '', $excluded_order_statuses = null ) {
			global $wpdb;

			if ( is_null( $excluded_order_statuses ) ) {
				$excluded_order_statuses = apply_filters( 'pafw_default_excluded_order_status', self::$default_excluded_order_status );
			}

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$date_where           = "AND ( posts.post_date BETWEEN '{$date_from}' AND '{$date_to}' )";
			$order_statuses_where = "'" . implode( "','", array_diff( array_keys( wc_get_order_statuses() ), $excluded_order_statuses ) ) . "'";

			$sql = "SELECT DATE_FORMAT(posts.post_date, '%Y-%m-%d') date, SUM(postmeta.meta_value) value
					FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->postmeta}  as postmeta ON posts.ID = postmeta.post_id
					WHERE
						postmeta.meta_key = '_order_total' AND
						posts.post_type = 'shop_order' AND
						posts.post_status IN ( {$order_statuses_where}  )
						{$date_where}
					GROUP BY date";

			$results = $wpdb->get_results( $sql, ARRAY_A );
			$start_date = date( 'Y-m-d', strtotime( $date_from ) );
			$end_date   = date( 'Y-m-d', strtotime( $date_to ) );

			if ( count( $results ) == 0 || $results[0]['date'] != $start_date ) {
				array_unshift( $results, array ( 'date' => $start_date, 'value' => '0' ) );
			}

			if ( $results[ count( $results ) - 1 ]['date'] != $end_date ) {
				$results[] = array ( 'date' => $end_date, 'value' => '0' );
			}

			return $results;
		}
		static function get_weekly_sales_by_date( $date_from, $date_to = '', $excluded_order_statuses = null ) {
			global $wpdb;

			if ( is_null( $excluded_order_statuses ) ) {
				$excluded_order_statuses = apply_filters( 'pafw_default_excluded_order_status', self::$default_excluded_order_status );
			}

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$date_where           = "AND ( posts.post_date BETWEEN '{$date_from}' AND '{$date_to}' )";
			$order_statuses_where = "'" . implode( "','", array_diff( array_keys( wc_get_order_statuses() ), $excluded_order_statuses ) ) . "'";

			$sql = "SELECT YEAR(posts.post_date) year, WEEK(posts.post_date) week, DATE_FORMAT(posts.post_date - INTERVAL (WEEKDAY(posts.post_date)+1) DAY, '%Y-%m-%d') date,SUM(postmeta.meta_value) value
					FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->postmeta}  as postmeta ON posts.ID = postmeta.post_id
					WHERE
						postmeta.meta_key = '_order_total' AND
						posts.post_type = 'shop_order' AND
						posts.post_status IN ( {$order_statuses_where}  )
						{$date_where}
					GROUP BY year, week";

			$results = $wpdb->get_results( $sql, ARRAY_A );
			$start_date = date( 'Y-m-d', strtotime( date( 'Y\WW', strtotime( $date_from ) ) . ' -1 days' ) );
			$end_date   = date( 'Y-m-d', strtotime( date( 'Y\WW', strtotime( $date_to ) ) . ' -1 days' ) );

			if ( count( $results ) == 0 || $results[0]['date'] != $start_date ) {
				array_unshift( $results, array ( 'date' => $start_date, 'value' => '0' ) );
			}

			if ( $results[ count( $results ) - 1 ]['date'] != $end_date ) {
				$results[] = array ( 'date' => $end_date, 'value' => '0' );
			}

			return $results;
		}
		static function get_monthly_sales_by_date( $date_from, $date_to = '', $excluded_order_statuses = null ) {
			global $wpdb;

			if ( is_null( $excluded_order_statuses ) ) {
				$excluded_order_statuses = apply_filters( 'pafw_default_excluded_order_status', self::$default_excluded_order_status );
			}

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$date_where           = "AND ( posts.post_date BETWEEN '{$date_from}' AND '{$date_to}' )";
			$order_statuses_where = "'" . implode( "','", array_diff( array_keys( wc_get_order_statuses() ), $excluded_order_statuses ) ) . "'";

			$sql = "SELECT YEAR(posts.post_date) year, MONTH(posts.post_date) month, DATE_FORMAT(posts.post_date, '%Y-%m-01') date,SUM(postmeta.meta_value) value
					FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->postmeta}  as postmeta ON posts.ID = postmeta.post_id
					WHERE
						postmeta.meta_key = '_order_total' AND
						posts.post_type = 'shop_order' AND
						posts.post_status IN ( {$order_statuses_where}  )
						{$date_where}
					GROUP BY year, month";

			$results = $wpdb->get_results( $sql, ARRAY_A );
			$start_date = date( 'Y-m-01', strtotime( $date_from ) );
			$end_date   = date( 'Y-m-01', strtotime( $date_to ) );

			if ( count( $results ) == 0 || $results[0]['date'] != $start_date ) {
				array_unshift( $results, array ( 'date' => $start_date, 'value' => '0' ) );
			}

			if ( $results[ count( $results ) - 1 ]['date'] != $end_date ) {
				$results[] = array ( 'date' => $end_date, 'value' => '0' );
			}

			return $results;
		}
		static function get_sales_by_hour( $date_from, $date_to = '', $excluded_order_statuses = null ) {
			global $wpdb;

			if ( is_null( $excluded_order_statuses ) ) {
				$excluded_order_statuses = apply_filters( 'pafw_default_excluded_order_status', self::$default_excluded_order_status );
			}

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$date_where           = "AND ( posts.post_date BETWEEN '{$date_from}' AND '{$date_to}' )";
			$order_statuses_where = "'" . implode( "','", array_diff( array_keys( wc_get_order_statuses() ), $excluded_order_statuses ) ) . "'";

			$sql = "SELECT HOUR(posts.post_date ) hour, count( posts.ID) count, SUM(postmeta.meta_value) value
					FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->postmeta}  as postmeta ON posts.ID = postmeta.post_id
					WHERE
						postmeta.meta_key = '_order_total' AND
						posts.post_type = 'shop_order' AND
						posts.post_status IN ( {$order_statuses_where}  )
						{$date_where}
					GROUP BY hour";

			return $wpdb->get_results( $sql, ARRAY_A );
		}
		static function get_sales_by_day_of_week( $date_from, $date_to = '', $excluded_order_statuses = null ) {
			global $wpdb;

			if ( is_null( $excluded_order_statuses ) ) {
				$excluded_order_statuses = apply_filters( 'pafw_default_excluded_order_status', self::$default_excluded_order_status );
			}

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$date_where           = "AND ( posts.post_date BETWEEN '{$date_from}' AND '{$date_to}' )";
			$order_statuses_where = "'" . implode( "','", array_diff( array_keys( wc_get_order_statuses() ), $excluded_order_statuses ) ) . "'";

			$sql = "SELECT DAYOFWEEK(posts.post_date) day_of_week, count(posts.ID) count, SUM(postmeta.meta_value) value
					FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->postmeta}  as postmeta ON posts.ID = postmeta.post_id
					WHERE
						postmeta.meta_key = '_order_total' AND
						posts.post_type = 'shop_order' AND
						posts.post_status IN ( {$order_statuses_where}  )
						{$date_where}
					GROUP BY day_of_week";

			$day_of_weeks = array (
				'1' => '일요일',
				'2' => '월요일',
				'3' => '화요일',
				'4' => '수요일',
				'5' => '목요일',
				'6' => '금요일',
				'7' => '토요일'
			);

			$result       = $wpdb->get_results( $sql, ARRAY_A );
			$keys         = wp_list_pluck( $result, 'day_of_week' );
			$keys         = array_flip( $keys );
			$missing_data = array_diff_key( $day_of_weeks, $keys );
			foreach ( $missing_data as $key => $value ) {
				$result[] = array (
					'day_of_week' => (string) $key,
					'count'       => "0",
					'value'       => "0"
				);
			}

			$sort_result = array();
			foreach ( $result as $value ) {
				$sort_result[ $value['day_of_week'] ] = $value;
				$sort_result[ $value['day_of_week'] ]['day_of_week'] = $day_of_weeks[ $value['day_of_week'] ];
			}

			ksort( $sort_result );

			$result = array_values( $sort_result );

			return $result;

		}
		static function get_sales_by_order_status( $date_from, $date_to = '' ) {
			global $wpdb;

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$date_where = "( posts.post_date BETWEEN '{$date_from}' AND '{$date_to}' )";

			$sql = "SELECT posts.post_status order_status, count( posts.ID ) count, SUM(postmeta.meta_value) amount
					FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->postmeta}  as postmeta ON posts.ID = postmeta.post_id
					WHERE
						postmeta.meta_key = '_order_total'
						AND posts.post_type = 'shop_order'
						AND {$date_where}
					GROUP BY order_status";

			$result = $wpdb->get_results( $sql, ARRAY_A );

			$merged_order_statuses = array (
				'processing' => array (
					'place-order'
				)
			);

			$merged_order_statuses = apply_filters( 'pafw_sales_statistics_merged_order_statuses', $merged_order_statuses );

			if ( ! empty( $merged_order_statuses ) ) {
				$_result = array ();
				foreach ( $result as $item ) {
					$_result[ $item['order_status'] ] = $item;
				}

				$result = $_result;

				foreach ( $merged_order_statuses as $key => $order_statuses ) {
					if ( empty( $result[ 'wc-' . $key ] ) ) {
						$result[ 'wc-' . $key ] = array (
							'order_status' => 'wc-' . $key,
							'count'        => 0,
							'amount'       => 0
						);
					}

					foreach ( $order_statuses as $order_status ) {
						if ( ! empty( $result[ 'wc-' . $order_status ] ) ) {
							$result[ 'wc-' . $key ]['count']  += $result[ 'wc-' . $order_status ]['count'];
							$result[ 'wc-' . $key ]['amount'] += $result[ 'wc-' . $order_status ]['amount'];
							unset( $result[ 'wc-' . $order_status ] );
						}
					}
				}
			}

			foreach ( $result as &$item ) {
				$item['amount'] = number_format( $item['amount'] );
				$item['count']  = number_format( $item['count'] );
			}

			return $result;
		}

		static function get_data() {
			$date_from = $_REQUEST['date_from'] . ' 00:00:00';
			$date_to   = $_REQUEST['date_to'] . ' 23:59:59';
			$interval  = $_REQUEST['interval'];

			if ( '1d' == $interval ) {
				$data = self::get_daily_sales_by_date( $date_from, $date_to );
			} else if ( '1w' == $interval ) {
				$data = self::get_weekly_sales_by_date( $date_from, $date_to );
			} else if ( '1M' == $interval ) {
				$data = self::get_monthly_sales_by_date( $date_from, $date_to );
			}

			wp_send_json_success( array (
				'order_stat_by_date'         => $data,
				'order_stat_by_day_of_week'  => self::get_sales_by_day_of_week( $date_from, $date_to ),
				'order_stat_by_hour'         => self::get_sales_by_hour( $date_from, $date_to ),
				'order_stat_by_order_status' => self::get_sales_by_order_status( $date_from, $date_to ),
			) );
		}
	}

endif;
