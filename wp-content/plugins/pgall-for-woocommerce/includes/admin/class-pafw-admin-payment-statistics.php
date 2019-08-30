<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'PAFW_Admin_Payment_Statistics' ) ) :

	class PAFW_Admin_Payment_Statistics {
		static function get_daily_statistics( $date_from, $date_to = '' ) {
			global $wpdb;

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$sql = "SELECT 
						DATE_FORMAT(date, '%Y-%m-%d') pafw_date, 
						SUM(CASE result_code WHEN 0 THEN 1 ELSE 0 END) success, 
						SUM(CASE result_code WHEN -1 THEN 1 ELSE 0 END) request, 
						SUM(CASE result_code WHEN 1 THEN 1 ELSE 0 END) positive, 
						SUM(CASE result_code WHEN 2 THEN 1 ELSE 0 END) negative,
						COUNT(id) total
					FROM {$wpdb->prefix}pafw_transaction
					WHERE
						date BETWEEN '{$date_from}' AND '{$date_to}'
					GROUP BY pafw_date";

			$results = $wpdb->get_results( $sql, ARRAY_A );
			$start_date = date( 'Y-m-d', strtotime( $date_from ) );
			$end_date   = date( 'Y-m-d', strtotime( $date_to ) );

			if ( count( $results ) == 0 || $results[0]['pafw_date'] != $start_date ) {
				array_unshift( $results, array ( 'pafw_date' => $start_date, 'success' => '0', 'request' => '0', 'positive' => '0', 'negative' => '0' ) );
			}

			if ( $results[ count( $results ) - 1 ]['pafw_date'] != $end_date ) {
				$results[] = array ( 'pafw_date' => $end_date, 'success' => '0', 'request' => '0', 'positive' => '0', 'negative' => '0' );
			}

			return $results;
		}
		static function get_weekly_statistics( $date_from, $date_to = '' ) {
			global $wpdb;

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$sql = "SELECT
						YEAR(date) year, 
						WEEK(date) week, 
						DATE_FORMAT(date - INTERVAL (WEEKDAY(date)+1) DAY, '%Y-%m-%d') pafw_date, 
						SUM(CASE result_code WHEN 0 THEN 1 ELSE 0 END) success, 
						SUM(CASE result_code WHEN -1 THEN 1 ELSE 0 END) request, 
						SUM(CASE result_code WHEN 1 THEN 1 ELSE 0 END) positive, 
						SUM(CASE result_code WHEN 2 THEN 1 ELSE 0 END) negative
					FROM {$wpdb->prefix}pafw_transaction
					WHERE
						date BETWEEN '{$date_from}' AND '{$date_to}'
					GROUP BY year, week";

			$results = $wpdb->get_results( $sql, ARRAY_A );
			$start_date = date( 'Y-m-d', strtotime( date( 'Y\WW', strtotime( $date_from ) ) . ' -1 days' ) );
			$end_date   = date( 'Y-m-d', strtotime( date( 'Y\WW', strtotime( $date_to ) ) . ' -1 days' ) );

			if ( count( $results ) == 0 || $results[0]['pafw_date'] != $start_date ) {
				array_unshift( $results, array ( 'pafw_date' => $start_date, 'success' => '0', 'request' => '0', 'positive' => '0', 'negative' => '0' ) );
			}

			if ( $results[ count( $results ) - 1 ]['pafw_date'] != $end_date ) {
				$results[] = array ( 'pafw_date' => $end_date, 'success' => '0', 'request' => '0', 'positive' => '0', 'negative' => '0' );
			}

			return $results;
		}
		static function get_monthly_statistics( $date_from, $date_to = '' ) {
			global $wpdb;

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$sql = "SELECT
						YEAR(date) year, 
						MONTH(date) month, 
						DATE_FORMAT(date, '%Y-%m-01') pafw_date,
						SUM(CASE result_code WHEN 0 THEN 1 ELSE 0 END) success, 
						SUM(CASE result_code WHEN -1 THEN 1 ELSE 0 END) request, 
						SUM(CASE result_code WHEN 1 THEN 1 ELSE 0 END) positive, 
						SUM(CASE result_code WHEN 2 THEN 1 ELSE 0 END) negative
					FROM {$wpdb->prefix}pafw_transaction
					WHERE
						date BETWEEN '{$date_from}' AND '{$date_to}'
					GROUP BY year, month";

			$results = $wpdb->get_results( $sql, ARRAY_A );
			$start_date = date( 'Y-m-01', strtotime( $date_from ) );
			$end_date   = date( 'Y-m-01', strtotime( $date_to ) );

			if ( count( $results ) == 0 || $results[0]['pafw_date'] != $start_date ) {
				array_unshift( $results, array ( 'pafw_date' => $start_date, 'success' => '0', 'request' => '0', 'positive' => '0', 'negative' => '0' ) );
			}

			if ( $results[ count( $results ) - 1 ]['pafw_date'] != $end_date ) {
				$results[] = array ( 'pafw_date' => $end_date, 'success' => '0', 'request' => '0', 'positive' => '0', 'negative' => '0' );
			}

			return $results;
		}
		static function get_daily_sales_count_for_device( $date_from, $date_to = '' ) {
			global $wpdb;

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$sql = "SELECT 
						DATE_FORMAT(date, '%Y-%m-%d') pafw_date, 
						SUM(CASE device_type WHEN 'PC' THEN 1 ELSE 0 END) pc_count, 
						SUM(CASE device_type WHEN 'PC' THEN order_total ELSE 0 END) pc_amount, 
						SUM(CASE device_type WHEN 'PC' THEN 0 ELSE 1 END) mobile_count,
						SUM(CASE device_type WHEN 'PC' THEN 0 ELSE order_total END) mobile_amount
					FROM {$wpdb->prefix}pafw_transaction
					WHERE
						date BETWEEN '{$date_from}' AND '{$date_to}'
						AND result_code = 0
					GROUP BY pafw_date";

			$results = $wpdb->get_results( $sql, ARRAY_A );
			$start_date = date( 'Y-m-d', strtotime( $date_from ) );
			$end_date   = date( 'Y-m-d', strtotime( $date_to ) );

			if ( count( $results ) == 0 || $results[0]['pafw_date'] != $start_date ) {
				array_unshift( $results, array ( 'pafw_date' => $start_date, 'pc_count' => '0', 'pc_amount' => '0', 'mobile_count' => '0', 'mobile_amount' => '0' ) );
			}

			if ( $results[ count( $results ) - 1 ]['pafw_date'] != $end_date ) {
				$results[] = array ( 'pafw_date' => $end_date, 'pc_count' => '0', 'pc_amount' => '0', 'mobile_count' => '0', 'mobile_amount' => '0' );
			}

			return $results;
		}
		static function get_weekly_sales_count_for_device( $date_from, $date_to = '' ) {
			global $wpdb;

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$sql = "SELECT
						YEAR(date) year, 
						WEEK(date) week, 
						DATE_FORMAT(date - INTERVAL (WEEKDAY(date)+1) DAY, '%Y-%m-%d') pafw_date, 
						SUM(CASE device_type WHEN 'PC' THEN 1 ELSE 0 END) pc_count, 
						SUM(CASE device_type WHEN 'PC' THEN order_total ELSE 0 END) pc_amount, 
						SUM(CASE device_type WHEN 'PC' THEN 0 ELSE 1 END) mobile_count,
						SUM(CASE device_type WHEN 'PC' THEN 0 ELSE order_total END) mobile_amount
					FROM {$wpdb->prefix}pafw_transaction
					WHERE
						date BETWEEN '{$date_from}' AND '{$date_to}'
						AND result_code = 0
					GROUP BY year, week";

			$results = $wpdb->get_results( $sql, ARRAY_A );
			$start_date = date( 'Y-m-d', strtotime( date( 'Y\WW', strtotime( $date_from ) ) . ' -1 days' ) );
			$end_date   = date( 'Y-m-d', strtotime( date( 'Y\WW', strtotime( $date_to ) ) . ' -1 days' ) );

			if ( count( $results ) == 0 || $results[0]['pafw_date'] != $start_date ) {
				array_unshift( $results, array ( 'pafw_date' => $start_date, 'pc_count' => '0', 'pc_amount' => '0', 'mobile_count' => '0', 'mobile_amount' => '0' ) );
			}

			if ( $results[ count( $results ) - 1 ]['pafw_date'] != $end_date ) {
				$results[] = array ( 'pafw_date' => $end_date, 'pc_count' => '0', 'pc_amount' => '0', 'mobile_count' => '0', 'mobile_amount' => '0' );
			}

			return $results;
		}
		static function get_monthly_sales_count_for_device( $date_from, $date_to = '' ) {
			global $wpdb;

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$sql = "SELECT
						YEAR(date) year, 
						MONTH(date) month, 
						DATE_FORMAT(date, '%Y-%m-01') pafw_date,
						DATE_FORMAT(date - INTERVAL (WEEKDAY(date)+1) DAY, '%Y-%m-%d') pafw_date, 
						SUM(CASE device_type WHEN 'PC' THEN 1 ELSE 0 END) pc_count, 
						SUM(CASE device_type WHEN 'PC' THEN order_total ELSE 0 END) pc_amount, 
						SUM(CASE device_type WHEN 'PC' THEN 0 ELSE 1 END) mobile_count,
						SUM(CASE device_type WHEN 'PC' THEN 0 ELSE order_total END) mobile_amount
					FROM {$wpdb->prefix}pafw_transaction
					WHERE
						date BETWEEN '{$date_from}' AND '{$date_to}'
						AND result_code = 0
					GROUP BY year, month";

			$results = $wpdb->get_results( $sql, ARRAY_A );
			$start_date = date( 'Y-m-01', strtotime( $date_from ) );
			$end_date   = date( 'Y-m-01', strtotime( $date_to ) );

			if ( count( $results ) == 0 || $results[0]['pafw_date'] != $start_date ) {
				array_unshift( $results, array ( 'pafw_date' => $start_date, 'pc_count' => '0', 'pc_amount' => '0', 'mobile_count' => '0', 'mobile_amount' => '0' ) );
			}

			if ( $results[ count( $results ) - 1 ]['pafw_date'] != $end_date ) {
				$results[] = array ( 'pafw_date' => $end_date, 'pc_count' => '0', 'pc_amount' => '0', 'mobile_count' => '0', 'mobile_amount' => '0' );
			}

			return $results;
		}
		static function get_payment_statistic( $date_from, $date_to = '' ) {
			global $wpdb;

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$sql = "SELECT 
						SUM(CASE result_code WHEN 0 THEN 1 ELSE 0 END) success, 
						SUM(CASE result_code WHEN -1 THEN 1 ELSE 0 END) request, 
						SUM(CASE result_code WHEN 1 THEN 1 ELSE 0 END) positive, 
						SUM(CASE result_code WHEN 2 THEN 1 ELSE 0 END) negative
					FROM {$wpdb->prefix}pafw_transaction
					WHERE
						date BETWEEN '{$date_from}' AND '{$date_to}'";

			$result = $wpdb->get_row( $sql, ARRAY_A );

			$completed = $result['success'];
			$cancelled = $result['positive'];
			$failed    = $result['negative'];
			$total     = $completed + $cancelled + $failed;

			$response = array (
				'request'   => array ( 'count' => number_format( $total ) ),
				'completed' => array ( 'count' => number_format( $completed ), 'percent' => 0 == $total ? 0 : number_format( $completed / $total * 100, 1 ) ),
				'cancelled' => array ( 'count' => number_format( $cancelled ), 'percent' => 0 == $total ? 0 : number_format( $cancelled / $total * 100, 1 ) ),
				'failed'    => array ( 'count' => number_format( $failed ), 'percent' => 0 == $total ? 0 : number_format( $failed / $total * 100, 1 ) )
			);

			return $response;
		}

		static function get_count_by_result_code( $date_from, $date_to = '' ) {
			global $wpdb;

			$date_where = "date BETWEEN '{$date_from}' AND '{$date_to}'";

			$sql = "SELECT 
						result_code, count( id ) count 
					FROM {$wpdb->prefix}pafw_transaction
					WHERE
						{$date_where}
					GROUP BY result_code";

			$result = $wpdb->get_results( $sql, ARRAY_A );

			$success = 0;
			$cancel  = 0;
			$fail    = 0;

			foreach ( $result as $item ) {
				if ( '0000' == $item['result_code'] ) {
					$success += $item['count'];
				} else if ( in_array( $item['result_code'], array ( '1001', '1002' ) ) ) {
					$cancel += $item['count'];
				} else {
					$fail += $item['count'];
				}
			}


			$statuses = array (
				'negative' => array (
					array (
						'label' => '결제성공',
						'value' => $success
					),
					array (
						'label' => '결제실패',
						'value' => $fail + $cancel,
						'color' => '#cc4748'
					)
				),
				'positive' => array (
					array (
						'label' => '결제성공',
						'value' => $success + $cancel
					),
					array (
						'label' => '결제실패',
						'value' => $fail,
						'color' => '#cc4748'
					)
				)
			);

			return $statuses;
		}

		static function get_failed_count_by_payment_method( $date_from, $date_to = '' ) {
			global $wpdb;

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$sql = "SELECT payment_method, payment_method_title, count(id) count
					FROM {$wpdb->prefix}pafw_transaction
					WHERE
						result_code NOT IN ('0000','9001','1001','1002')
						AND date BETWEEN '{$date_from}' AND '{$date_to}'
					GROUP BY payment_method";

			$result = $wpdb->get_results( $sql, ARRAY_A );

			return $result;
		}

		static function get_failed_count_by_device_type( $date_from, $date_to = '' ) {
			global $wpdb;

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$sql = "SELECT device_type, count(id) count
					FROM {$wpdb->prefix}pafw_transaction
					WHERE
						result_code = 2
						AND date BETWEEN '{$date_from}' AND '{$date_to}'
					GROUP BY device_type";

			$result = $wpdb->get_results( $sql, ARRAY_A );

			return $result;
		}

		static function get_completed_count_by_payment_method( $date_from, $date_to = '' ) {
			global $wpdb;

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$sql = "SELECT payment_method, payment_method_title, count(id) count
					FROM {$wpdb->prefix}pafw_transaction
					WHERE
						result_code = 0
						AND date BETWEEN '{$date_from}' AND '{$date_to}'
					GROUP BY payment_method
					ORDER BY count DESC";

			$result = $wpdb->get_results( $sql, ARRAY_A );

			return $result;
		}

		static function get_failed_count_by_result_code( $date_from, $date_to = '' ) {
			global $wpdb;

			if ( empty( $date_to ) ) {
				$date_to = date( 'Y-m-d 23:59:59' );
			}

			$sql = "SELECT error_code, CONCAT( '[', error_code, '] ', result_message) message, count(id) error_count
					FROM {$wpdb->prefix}pafw_transaction
					WHERE
						result_code = 2
						AND date BETWEEN '{$date_from}' AND '{$date_to}'
					GROUP BY error_code
					ORDER BY error_count DESC";

			$result = $wpdb->get_results( $sql, ARRAY_A );

			return $result;
		}

		static function get_sales_by_payment_method( $date_from, $date_to = '', $order_statuses = array ( 'wc-cancelled', 'wc-failed', 'wc-on-hold', 'wc-pending' ) ) {
			global $wpdb;

			$date_where           = "AND ( posts.post_date BETWEEN '{$date_from}' AND '{$date_to}' )";
			$order_statuses_where = "'" . implode( "','", $order_statuses ) . "'";

			$sql = "SELECT paymethod.meta_value payment_method, SUM(postmeta.meta_value) value
					FROM {$wpdb->posts} posts
					LEFT JOIN {$wpdb->postmeta}  as postmeta ON posts.ID = postmeta.post_id AND postmeta.meta_key = '_order_total'
					LEFT JOIN {$wpdb->postmeta}  as paymethod ON posts.ID = paymethod.post_id AND paymethod.meta_key = '_payment_method_title'
					WHERE
						posts.post_type = 'shop_order' AND
						posts.post_status NOT IN ( {$order_statuses_where}  )
						{$date_where}
					GROUP BY payment_method";

			$result = $wpdb->get_results( $sql, ARRAY_A );

			return $result;
		}

		static function get_data() {
			$date_from = $_REQUEST['date_from'] . ' 00:00:00';
			$date_to   = $_REQUEST['date_to'] . ' 23:59:59';
			$interval  = $_REQUEST['interval'];

			if ( '1d' == $interval ) {
				$data              = self::get_daily_statistics( $date_from, $date_to );
				$device_sales_data = self::get_daily_sales_count_for_device( $date_from, $date_to );
			} else if ( '1w' == $interval ) {
				$data              = self::get_weekly_statistics( $date_from, $date_to );
				$device_sales_data = self::get_weekly_sales_count_for_device( $date_from, $date_to );
			} else if ( '1M' == $interval ) {
				$data              = self::get_monthly_statistics( $date_from, $date_to );
				$device_sales_data = self::get_monthly_sales_count_for_device( $date_from, $date_to );
			}

			wp_send_json_success( array (
				'payment_statistics'                => self::get_payment_statistic( $date_from, $date_to ),
				'order_stat_by_date'                => $data,
				'device_sales_data'                 => $device_sales_data,
				'count_by_result_code'              => self::get_count_by_result_code( $date_from, $date_to ),
				'failed_count_by_device_type'       => self::get_failed_count_by_device_type( $date_from, $date_to ),
				'completed_count_by_payment_method' => self::get_completed_count_by_payment_method( $date_from, $date_to ),
				'failed_count_by_result_code'       => self::get_failed_count_by_result_code( $date_from, $date_to ),
			) );
		}
	}

endif;
