<?php


//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	abstract class PAFW_Payment_Gateway extends WC_Payment_Gateway {

		protected static $logger = null;

		protected $pg_title = null;

		protected $master_id = '';

		public function __construct() {
			$settings = pafw_get_settings( $this->id );

			if ( $settings ) {
				$this->settings = $settings->get_settings();

				$this->adjust_settings();

				$this->countries  = array ( 'KR' );
				$this->has_fields = false;
				$this->enabled    = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';

				if ( is_checkout() && 'production' != pafw_get( $this->settings, 'operation_mode', 'production' ) ) {
					$user = wp_get_current_user();

					if ( ! current_user_can( 'manage_options' ) && ( empty( $user ) || ! is_user_logged_in() || $user->user_login != pafw_get( $this->settings, 'test_user_id' ) ) ) {
						$this->enabled = 'no';
					}
				}
				add_action( 'woocommerce_thankyou_' . $this->id, array ( $this, 'thankyou_page' ) );
				add_action( 'woocommerce_api_wc_gateway_' . $this->id, array ( $this, 'process_payment_response' ) );
			}

			$this->supports[] = 'pafw';
		}
		function adjust_settings() {
		}
		function __get( $key ) {
			return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $this->$key;
		}
		function is_vbank( $order = null ) {
			return $this->supports( 'pafw-vbank' );
		}
		function is_escrow( $order = null ) {
			return $this->supports( 'pafw-escrow' );
		}
		function add_log( $msg ) {
			if ( is_null( self::$logger ) ) {
				self::$logger = new WC_Logger();
			}

			self::$logger->add( $this->id, $msg );
		}
		function validate_payment_method_of_order( $order ) {
			return $this->id == pafw_get_object_property( $order, 'payment_method' );
		}
		public function woocommerce_payment_complete_order_status( $order_status, $order_id, $order = null ) {
			if ( ! empty( $this->settings['order_status_after_payment'] ) ) {
				$order_status = $this->settings['order_status_after_payment'];
			}

			return $order_status;
		}
		public function is_available() {
			if ( in_array( get_woocommerce_currency(), apply_filters( 'pafw_supported_currencies', array ( 'KRW' ) ) ) ) {
				return parent::is_available();
			} else {
				return false;
			}
		}
		public function is_refundable( $order, $screen = 'admin' ) {
			if ( 'admin' == $screen ) {
				return ! in_array( $order->get_status(), array ( 'completed', 'refunded', 'cancelled' ) );
			} else {
				$order_statuses = $this->settings[ 'possible_refund_status_for_' . $screen ];

				return is_array( $order_statuses ) && in_array( $order->get_status(), $order_statuses );
			}
		}
		public function is_fully_refundable( $order, $screen = 'admin' ) {
			return $this->is_refundable( $order, $screen );
		}
		function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			do_action( 'pafw_process_payment', $order );

			return array (
				'result'    => 'success',
				'redirect'  => $order->get_checkout_payment_url( true ),
				'order_id'  => pafw_get_object_property( $order, 'id' ),
				'order_key' => pafw_get_object_property( $order, 'order_key' )
			);
		}
		function make_product_info( $order ) {
			$product_info = '';

			$items = $order->get_items();

			if ( ! empty( $items ) ) {
				if ( count( $items ) == 1 ) {
					$keys = array_keys( $items );

					$product_info = $items[ $keys[0] ]['name'];
				} else {
					$keys = array_keys( $items );

					$product_info = sprintf( __( '%s 외 %d건', 'pgall-for-woocommerce' ), $items[ $keys[0] ]['name'], count( $items ) - 1 );
				}
			}

			return apply_filters( 'pafw_product_info', $product_info, $order );
		}
		function has_enough_stock( $order ) {

			if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {

				foreach ( $order->get_items() as $item ) {

					$_product = $order->get_product_from_item( $item );

					if ( $_product && $_product->exists() ) {
						if ( $_product->managing_stock() && ! $_product->has_enough_stock( $item['qty'] ) ) {
							throw new PAFW_Exception( sprintf( __( '결제오류 : [%d] %s 상품의 재고가 부족합니다.', 'pgall-for-woocommerce' ), $_product->get_id(), $_product->get_title() ), '1101', 'PAFW-1101' );
						}
					}
				}
			}
		}
		function check_requirement() {
			//PHP 확장 동작 여부 확인
			if ( ! function_exists( 'openssl_digest' ) ) {
				throw new Exception( __( '[ERR-PAFW-0001] PHP OpenSSL 확장이 설치되어 있지 않아 이용할 수 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ) );
			}

			if ( ! function_exists( 'hash' ) ) {
				throw new Exception( __( '[ERR-PAFW-0002] PHP MCrypt 확장이 설치되어 있지 않아 이용할 수 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ) );
			}

			if ( ! function_exists( 'mb_convert_encoding' ) ) {
				throw new Exception( __( '[ERR-PAFW-0002] PHP MBString 확장이 설치되어 있지 않아 이용할 수 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ) );
			}
		}
		function thankyou_page( $order_id ) {
			$order = wc_get_order( $order_id );

			do_action( 'pafw_thankyou_page', $order );

			if ( $this->is_vbank( $order ) ) {
				wc_get_template( 'pafw/vbank_acc_info.php', array ( 'order' => $order ), '', PAFW()->template_path() );
			} else {
				wc_get_template( 'pafw/thankyou_page.php', array ( 'payment_method' => $this->id, 'title' => $this->title ), '', PAFW()->template_path() );
			}
		}
		function redirect_page( $order_id ) {

			$redirect_url = home_url();

			if ( empty( $order_id ) ) {
				$this->add_log( "Redirect : Home" );
			} else {
				$order = wc_get_order( $order_id );

				if ( $order ) {
					if ( $order->get_status() == 'pending' ) {
						$this->add_log( "Redirect : Checkout" );
						$redirect_url = wc_get_page_permalink( 'checkout' );
					} else {
						$this->add_log( "Redirect : Order Received" );
						$redirect_url = $order->get_checkout_order_received_url();
					}
				} else {
					if ( is_user_logged_in() ) {
						$myaccount_page_id = get_option( 'woocommerce_myaccount_page_id', true );
						if ( ! empty( $myaccount_page_id ) ) {
							$this->add_log( "Redirect : My Account" );
							$redirect_url = get_permalink( $myaccount_page_id );
						}
					} else {
						$this->add_log( "Redirect : Referer" );
						$redirect_url = $_SERVER['HTTP_REFERER'];
					}
				}
			}

			wp_safe_redirect( apply_filters( 'pafw_redirect_url', $redirect_url, $order_id ) );

			die();
		}
		public function check_shop_order_capability() {
			if ( ! current_user_can( 'publish_shop_orders' ) ) {
				throw new Exception( __( '주문 관리 권한이 없습니다.', 'pgall-for-woocommerce' ) );
			}
		}
		public function get_order( $order_id = null, $order_key = null ) {
			$order = apply_filters( 'pafw_get_order', null );

			if ( is_null( $order ) ) {
				if ( is_null( $order_id ) && isset( $_REQUEST['order_id'] ) ) {
					$order_id = $_REQUEST['order_id'];
				}

				if ( is_null( $order_key ) && isset( $_REQUEST['order_key'] ) ) {
					$order_key = $_REQUEST['order_key'];
				}

				if ( is_null( $order_id ) ) {
					throw new PAFW_Exception( __( '필수 파라미터가 누락되었습니다. [주문아이디]', 'pgall-for-woocommerce' ), '1001', 'PAFW-1001' );
				}

				$order = wc_get_order( $order_id );

				if ( ! $order ) {
					throw new PAFW_Exception( __( '주문을 찾을 수 없습니다.', 'pgall-for-woocommerce' ), '1002', 'PAFW-1002' );
				}

				if ( ! is_null( $order_key ) && pafw_get_object_property( $order, 'order_key' ) != $order_key ) {
					throw new PAFW_Exception( __( '주문 정보가 올바르지 않습니다.', 'pgall-for-woocommerce' ), '1003', 'PAFW-1003' );
				}
			}

			return $order;
		}
		public function get_vbank_payment_datas( $order ) {
			$vact_bank_code_name = pafw_get_meta( $order, '_pafw_vacc_bank_name' );
			$vact_num            = pafw_get_meta( $order, '_pafw_vacc_num' );
			$vact_holder         = pafw_get_meta( $order, '_pafw_vacc_holder' );
			$vact_depositor      = pafw_get_meta( $order, '_pafw_vacc_depositor' );
			$vact_date           = pafw_get_meta( $order, '_pafw_vacc_date' );
			$vact_date_format    = date( __( 'Y년 m월 d일', 'pgall-for-woocommerce' ), strtotime( $vact_date ) );
			$vbank_noti_received = pafw_get_meta( $order, '_pafw_vbank_noti_received' );
			$vbank_noti_data     = array (
				'입금상태' => 'yes' == $vbank_noti_received ? '<span style="color:blue;">입금완료</span>' : '<span style="color:red;">입금대기중</span>',
			);
			if ( 'yes' == $vbank_noti_received ) {
				$tranaction_date = preg_replace( '/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1-$2-$3 $4:$5:$6', pafw_get_meta( $order, '_pafw_vbank_noti_transaction_date' ) );
				$deposit_bank    = pafw_get_meta( $order, '_pafw_vbank_noti_deposit_bank' );
				$depositor       = pafw_get_meta( $order, '_pafw_vbank_noti_depositor' );

				$vbank_noti_data = array_merge( $vbank_noti_data, array (
					'입금일시' => $tranaction_date,
					'입금은행' => $deposit_bank,
					'입금자'  => $depositor
				) );

				$vbank_noti_data = array_filter( $vbank_noti_data );
			}

			return array (
				array (
					'title' => sprintf( __( '결제정보 [%s]', 'pgall-for-woocommerce' ), pafw_get_object_property( $order, 'payment_method_title' ) ),
					'data'  => array_filter( array (
						'입금은행' => $vact_bank_code_name,
						'입금계좌' => $vact_num,
						'예금주'  => $vact_holder,
						'송금자'  => $vact_depositor,
						'입금기한' => $vact_date_format,
						'결제장치' => pafw_get_meta( $order, '_pafw_device_type' )
					) )
				),
				array (
					'title' => __( '입금정보', 'pgall-for-woocommerce' ),
					'data'  => $vbank_noti_data
				)
			);
		}
		public function get_escrow_bank_payment_datas( $order ) {
			$payment_datas = array ();
			$payed_date = preg_replace( '/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1-$2-$3 $4:$5:$6', pafw_get_meta( $order, '_pafw_payed_date' ) );
			$bank_code  = pafw_get_meta( $order, '_pafw_bank_code' );
			$bank_name  = pafw_get_meta( $order, '_pafw_bank_name' );

			$payment_datas[] = array (
				'title' => sprintf( __( '결제정보 [%s]', 'pgall-for-woocommerce' ), pafw_get_object_property( $order, 'payment_method_title' ) ),
				'data'  => array (
					'승인일시'  => $payed_date,
					'은행명'   => sprintf( '%s [%s]', $bank_name, $bank_code ),
					'결제장치'  => pafw_get_meta( $order, '_pafw_device_type' ),
					'현금영수증' => $this->get_cash_receipts( $order )
				)
			);
			$register_delivery_info = 'yes' == pafw_get_meta( $order, '_pafw_escrow_register_delivery_info' );
			$delivery_info          = array (
				'배송정보' => $register_delivery_info ? '<span style="color:blue;">등록완료</span>' : '<span style="color:red;">미등록</span>',
			);
			if ( $register_delivery_info ) {
				$delivery_company_name  = $this->delivery_company_name;
				$delivery_register_name = $this->delivery_register_name;
				$tracking_number        = pafw_get_meta( $order, '_pafw_escrow_tracking_number' );

				$delivery_info = array_merge( $delivery_info, array (
					'배송정보 등록자' => $delivery_register_name,
					'택배사명'     => $delivery_company_name,
					'송장번호'     => $tracking_number
				) );
			}

			$payment_datas[] = array (
				'title' => __( '에스크로 배송정보', 'pgall-for-woocommerce' ),
				'data'  => $delivery_info
			);
			if ( $register_delivery_info ) {
				$is_confirmed = 'yes' == pafw_get_meta( $order, '_pafw_escrow_order_confirm' );
				$confirm_time = pafw_get_meta( $order, '_pafw_escrow_order_confirm_time' );
				$is_rejected  = 'yes' == pafw_get_meta( $order, '_pafw_escrow_order_confirm_reject' );
				$reject_time  = pafw_get_meta( $order, '_pafw_escrow_order_confirm_reject_time' );

				if ( $is_confirmed ) {
					$confirm_info['상태']   = __( '<span style="color:blue;">구매확인</span>', 'pgall-for-woocommerce' );
					$confirm_info['확인일시'] = $confirm_time;
				} else if ( $is_rejected ) {
					$confirm_info['상태']   = __( '<span style="color:red;">구매거절</span>', 'pgall-for-woocommerce' );
					$confirm_info['거절일시'] = $reject_time;
				} else {
					$confirm_info['상태'] = __( '<span style="color:red;">구매결정 대기</span>', 'pgall-for-woocommerce' );
				}

				$payment_datas[] = array (
					'title' => __( '구매 확인/거절', 'pgall-for-woocommerce' ),
					'data'  => $confirm_info
				);
			}

			return $payment_datas;
		}
		public function get_payment_datas( $order ) {
			$card_info  = '';
			$payed_date = preg_replace( '/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1-$2-$3 $4:$5:$6', pafw_get_meta( $order, '_pafw_payed_date' ) );
			$card_name  = pafw_get_meta( $order, '_pafw_card_name' );
			$cart_num   = trim( pafw_get_meta( $order, '_pafw_card_num' ) );

			if ( 12 == strlen( $cart_num ) ) {
				$card_num = preg_replace( '/([0-9]{4})([0-9]{4})([0-9]{4})/', '$1-$2-$3-0000', $cart_num );
			} else if ( 16 == strlen( $cart_num ) ) {
				$card_num = implode( '-', str_split( $cart_num, 4 ) );
			}
			if ( ! empty( $card_name ) && ! empty( $card_num ) ) {
				$card_info = sprintf( '%s (%s)', $card_name, $card_num );
			}

			$bank_info = '';
			$bank_code = pafw_get_meta( $order, '_pafw_bank_code' );
			$bank_name = pafw_get_meta( $order, '_pafw_bank_name' );
			if ( ! empty( $bank_code ) && ! empty( $bank_name ) ) {
				$bank_info = sprintf( '%s [%s]', $bank_name, $bank_code );
			}

			$total_price = pafw_get_meta( $order, '_pafw_total_price' );
			if ( empty( $total_price ) ) {
				$total_price = $order->get_total();
			}

			return array (
				array (
					'title' => sprintf( __( '결제정보 [%s]', 'pgall-for-woocommerce' ), pafw_get_object_property( $order, 'payment_method_title' ) ),
					'data'  => array_filter( array (
						'승인일시'  => $payed_date,
						'카드정보'  => $card_info,
						'은행명'   => $bank_info,
						'전화번호'  => pafw_get_meta( $order, '_pafw_hpp_num' ),
						'결제금액'  => wc_price( $total_price ),
						'결제장치'  => pafw_get_meta( $order, '_pafw_device_type' ),
						'현금영수증' => $this->get_cash_receipts( $order ),
					) )
				)
			);
		}
		public function get_subscription_payment_datas( $order ) {
			$card_info  = '';
			$payed_date = preg_replace( '/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1-$2-$3 $4:$5:$6', pafw_get_meta( $order, '_pafw_payed_date' ) );
			$card_name  = pafw_get_meta( $order, '_pafw_card_name' );
			$cart_num   = trim( pafw_get_meta( $order, '_pafw_card_num' ) );

			if ( 12 == strlen( $cart_num ) ) {
				$card_num = preg_replace( '/([0-9]{4})([0-9]{4})([0-9]{4})/', '$1-$2-$3-0000', $cart_num );
			} else if ( 16 == strlen( $cart_num ) ) {
				$card_num = implode( '-', str_split( $cart_num, 4 ) );
			}
			if ( ! empty( $card_name ) && ! empty( $card_num ) ) {
				$card_info = sprintf( '%s (%s)', $card_name, $card_num );
			}

			$bank_info = '';
			$bank_code = pafw_get_meta( $order, '_pafw_bank_code' );
			$bank_name = pafw_get_meta( $order, '_pafw_bank_name' );
			if ( ! empty( $bank_code ) && ! empty( $bank_name ) ) {
				$bank_info = sprintf( '%s [%s]', $bank_name, $bank_code );
			}

			$total_price = pafw_get_meta( $order, '_pafw_total_price' );
			if ( empty( $total_price ) ) {
				$total_price = $order->get_total();
			}

			$subscription_relation = ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order ) ) ? __( '갱신', 'pgall-for-woocommerce' ) : __( '신규', 'pgall-for-woocommerce' );

			return array (
				array (
					'title' => sprintf( __( '결제정보 [%s]', 'pgall-for-woocommerce' ), pafw_get_object_property( $order, 'payment_method_title' ) ),
					'data'  => array_filter( array (
						'정기결제'  => $subscription_relation,
						'승인일시'  => $payed_date,
						'카드정보'  => $card_info,
						'은행명'   => $bank_info,
						'전화번호'  => pafw_get_meta( $order, '_pafw_hpp_num' ),
						'결제금액'  => wc_price( $total_price ),
						'결제장치'  => pafw_get_meta( $order, '_pafw_device_type' ),
						'현금영수증' => $this->get_cash_receipts( $order ),
					) )
				)
			);
		}
		public function get_cancel_datas( $order ) {

			if ( 'yes' == pafw_get_meta( $order, '_pafw_order_cancelled' ) ) {
				$cancel_date = preg_replace( '/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/', '$1-$2-$3 $4:$5:$6', pafw_get_meta( $order, '_pafw_cancel_date' ) );

				if ( $this->is_vbank( $order ) ) {
					return array_filter( array (
						'환불일시' => $cancel_date,
						'환불은행' => pafw_get_meta( $order, '_pafw_vbank_refund_bank_name' ),
						'환불계좌' => pafw_get_meta( $order, '_pafw_vbank_refund_acc_num' ),
						'예금주'  => pafw_get_meta( $order, '_pafw_vbank_refund_acc_name' ),
						'환불사유' => pafw_get_meta( $order, '_pafw_vbank_refund_reason' ),
					) );
				} else {
					return array_filter( array (
						'취소일시' => $cancel_date
					) );
				}
			}

			return array ();
		}
		function add_meta_box_payment_info( $post ) {

			$order       = wc_get_order( $post );
			$tid         = $this->get_transaction_id( $order );
			$receipt_url = $this->get_transaction_url( $order );

			wp_enqueue_style( 'pafw-admin', PAFW()->plugin_url() . '/assets/css/admin.css', array (), PAFW_VERSION );

			wp_register_script( 'pafw-admin-js', PAFW()->plugin_url() . '/assets/js/admin.js', array (), PAFW_VERSION );
			wp_enqueue_script( 'pafw-admin-js' );
			wp_localize_script( 'pafw-admin-js', '_pafw_admin', array (
				'action'               => 'refund_request_' . $this->id,
				'order_id'             => pafw_get_object_property( $order, 'id' ),
				'tid'                  => $tid,
				'receipt_url'          => $receipt_url,
				'receipt_popup_params' => $this->get_receipt_popup_params(),
				'payment_method'       => $this->id,
				'slug'                 => PAFW()->slug(),
				'order_total'          => $order->get_total(),
				'_wpnonce'             => wp_create_nonce( 'pgall-for-woocommerce' )
			) );

			$is_refundable = $this->is_refundable( $order );
			$is_fullly_refundable = $this->is_fully_refundable( $order );

			if ( $this->is_vbank( $order ) ) {
				$payment_datas = $this->get_vbank_payment_datas( $order );
			} else if ( $this->is_escrow( $order ) ) {
				$payment_datas = $this->get_escrow_bank_payment_datas( $order );
			} else {
				$payment_datas = $this->get_payment_datas( $order );
			}
			$cancel_datas = $this->get_cancel_datas( $order );

			include( 'gateways/pafw/views/payment-info.php' );
		}
		function add_meta_box_subscription_payment_info( $post ) {

			$order       = wc_get_order( $post );
			$tid         = $this->get_transaction_id( $order );
			$receipt_url = $this->get_transaction_url( $order );

			wp_enqueue_style( 'pafw-admin', PAFW()->plugin_url() . '/assets/css/admin.css', array (), PAFW_VERSION );

			wp_register_script( 'pafw-admin-js', PAFW()->plugin_url() . '/assets/js/admin.js', array (), PAFW_VERSION );
			wp_enqueue_script( 'pafw-admin-js' );
			wp_localize_script( 'pafw-admin-js', '_pafw_admin', array (
				'action'               => 'refund_request_' . $this->id,
				'order_id'             => pafw_get_object_property( $order, 'id' ),
				'tid'                  => $tid,
				'receipt_url'          => $receipt_url,
				'receipt_popup_params' => $this->get_receipt_popup_params(),
				'payment_method'       => $this->id,
				'slug'                 => PAFW()->slug(),
				'order_total'          => $order->get_total(),
				'_wpnonce'             => wp_create_nonce( 'pgall-for-woocommerce' )
			) );

			$is_refundable = $this->is_refundable( $order );
			$is_fullly_refundable = $this->is_fully_refundable( $order );

			$payment_datas = $this->get_subscription_payment_datas( $order );

			$cancel_datas = $this->get_cancel_datas( $order );

			include( 'gateways/pafw/views/payment-info.php' );
		}
		function add_meta_box_escrow( $post ) {
			$order = wc_get_order( $post );

			$order_status = $order->get_status();

			$delivery_company_name  = $this->delivery_company_name;
			$delivery_register_name = $this->delivery_register_name;
			$tracking_number        = pafw_get_meta( $order, '_pafw_escrow_tracking_number' );
			$order_cancelled        = pafw_get_meta( $order, '_pafw_escrow_order_cancelled' );
			$register_delivery_info = 'yes' == pafw_get_meta( $order, '_pafw_escrow_register_delivery_info' );
			$is_cancelled           = 'yes' == pafw_get_meta( $order, '_pafw_escrow_order_cancelled' );
			$is_confirmed           = 'yes' == pafw_get_meta( $order, '_pafw_escrow_order_confirm' ) || 'yes' == pafw_get_meta( $order, '_pafw_escrow_order_confirm_reject' );
			$support_modify_delivery_info = $this->supports( 'pafw-escrow-support-modify-delivery-info' );

			include( 'gateways/pafw/views/escrow.php' );
		}
		function add_meta_box_vbank( $post ) {
			$order = wc_get_order( $post );

			$order_status = $order->get_status();
			$refundable   = $this->is_refundable( $order );

			include( 'gateways/pafw/views/vbank.php' );
		}
		function add_meta_box_additional_charge( $post ) {
			$order = wc_get_order( $post );

			include( 'gateways/pafw/views/additional_charge.php' );
		}
		function add_meta_box_subscriptions( $post ) {
			$subscription = wc_get_order( $post );

			include_once( 'gateways/pafw/views/subscription.php' );
		}
		public function add_meta_boxes( $order ) {
			if ( ! in_array( $order->get_status(), array ( 'pending', 'failed' ) ) ) {
				if ( $this->supports( 'subscriptions' ) ) {
					add_meta_box(
						'pafw-order-refund',
						$this->pg_title . ' ' . __( '결제내역 <span class="pafw-powerd"><a target="_blank" href="https://www.codemshop.com/">Powered by CodeM</a></span>', 'pgall-for-woocommerce' ),
						array ( $this, 'add_meta_box_subscription_payment_info' ),
						'shop_order',
						'side',
						'high'
					);
				} else {
					add_meta_box(
						'pafw-order-refund',
						$this->pg_title . ' ' . __( '결제내역 <span class="pafw-powerd"><a target="_blank" href="https://www.codemshop.com/">Powered by CodeM</a></span>', 'pgall-for-woocommerce' ),
						array ( $this, 'add_meta_box_payment_info' ),
						'shop_order',
						'side',
						'high'
					);
				}
			}
			if ( $this->is_vbank( $order ) && $this->supports( 'pafw-vbank-refund' ) && 'yes' == pafw_get_meta( $order, '_pafw_vbank_noti_received' ) && $this->is_refundable( $order ) ) {
				add_meta_box(
					'pafw-order-vbank',
					__( '가상계좌 환불 <span class="pafw-powerd"><a target="_blank" href="https://www.codemshop.com/">Powered by CodeM</a></span>', 'pgall-for-woocommerce' ),
					array ( $this, 'add_meta_box_vbank' ),
					'shop_order',
					'side',
					'high'
				);
			}

			if ( $this->is_escrow( $order ) && ! in_array( $order->get_status(), array ( 'completed', 'cancelled', 'refunded' ) ) ) {
				add_meta_box(
					'pafw-order-escrow',
					__( '에스크로 배송등록 <span class="pafw-powerd"><a target="_blank" href="https://www.codemshop.com/">Powered by CodeM</a></span>', 'pgall-for-woocommerce' ),
					array ( $this, 'add_meta_box_escrow' ),
					'shop_order',
					'side',
					'high'
				);
			}
			if ( 'shop_subscription' == $order->get_type() && $this->supports( 'subscriptions' ) ) {
				wp_register_script( 'pafw-admin-js', PAFW()->plugin_url() . '/assets/js/admin.js', array (), PAFW_VERSION );
				wp_enqueue_script( 'pafw-admin-js' );
				wp_localize_script( 'pafw-admin-js', '_pafw_admin', array (
					'slug'            => PAFW()->slug(),
					'subscription_id' => pafw_get_object_property( $order, 'id' ),
					'payment_method'  => $order->get_payment_method(),
					'_wpnonce'        => wp_create_nonce( 'pgall-for-woocommerce' )
				) );

				add_meta_box(
					'pafw-order-subscriptions',
					__( '정기결제 배치키 관리 <span class="pafw-powerd"><a target="_blank" href="https://www.codemshop.com/">Powered by CodeM</a></span>', 'pgall-for-woocommerce' ),
					array ( $this, 'add_meta_box_subscriptions' ),
					'shop_subscription',
					'side',
					'high'
				);
			}
		}
		function get_order_id_from_txnid( $txnid ) {
			$ids = explode( '_', $txnid );

			if ( count( $ids ) > 0 ) {
				return $ids[0];
			}

			return - 1;
		}
		function get_txnid( $order ) {
			$txnid = pafw_get_meta( $order, '_pafw_txnid', true );

			if ( empty( $txnid ) ) {
				$txnid = pafw_get_object_property( $order, 'id' ) . '_' . date( "ymd" ) . '_' . date( "his" );
				pafw_update_meta_data( $order, '_pafw_txnid', $txnid );
			}

			return $txnid;
		}
		function validate_txnid( $order, $txnid ) {
			return $txnid == pafw_get_meta( $order, '_pafw_txnid' );
		}
		function get_api_url( $type ) {
			$api_url = untrailingslashit( WC()->api_request_url( get_class( $this ) . '?type=' . $type, pafw_check_ssl() ) );

			return $api_url;
		}
		function validate_order_status( $order, $auto_cancel = false ) {
			if ( ! in_array( $order->get_status(), apply_filters( 'woocommerce_valid_order_statuses_for_payment', array ( 'on-hold', 'pending', 'failed' ), $order ) ) ) {
				$paid_date      = pafw_get_object_property( $order, 'paid_date' );
				$transaction_id = $this->get_transaction_id( $order );

				if ( empty( $paid_date ) || empty( $transaction_id ) ) {
					if ( $auto_cancel && ! empty( $transaction_id ) ) {
						$this->cancel_request( $order, '시스템 자동 취소 처리' );
					}
					throw new PAFW_Exception( sprintf( __( '유효하지 않은 주문입니다. 주문상태(%s)가 잘못 되었거나 결제 대기시간 초과로 취소된 주문입니다.', 'pgall-for-woocommerce' ), $order->get_status() ), '2001', 'PAFW-2001' );

				} else {
					throw new PAFW_Exception( __( '이미 결제가 완료된 주문입니다.', 'pgall-for-woocommerce' ), '2002', 'PAFW-2002' );
				}
			}
		}
		function woocommerce_view_order( $order_id, $order ) {
			if ( $this->is_vbank( $order ) ) {
				wc_get_template( 'pafw/vbank_acc_info.php', array ( 'order' => $order ), '', PAFW()->template_path() );
			}

			if ( $this->is_escrow( $order ) ) {
				if ( 'yes' == pafw_get_meta( $order, '_pafw_escrow_register_delivery_info' ) ) {
					$delivery_shipping_num = pafw_get_meta( $order, '_pafw_escrow_tracking_number' );
					$delivery_company_name = $this->delivery_company_name;

					if ( $this->supports( 'pafw-escrow-support-confirm-by-customer' ) ) {
						wp_enqueue_script( 'pafw_myaccount', PAFW()->plugin_url() . '/assets/gateways/' . $this->master_id . '/js/myaccount.js', array (), PAFW_VERSION );
						wp_localize_script( 'pafw_myaccount', '_pafw_myaccount', array (
							'ajaxurl'        => admin_url( 'admin-ajax.php' ),
							'payment_method' => $this->id,
							'order_id'       => $order_id,
							'order_key'      => pafw_get_object_property( $order, 'order_key' ),
							'slug'           => PAFW()->slug(),
							'_wpnonce'       => wp_create_nonce( 'pgall-for-woocommerce' )
						) );

						wc_get_template( 'pafw/' . $this->master_id . '/escrow.php', array (
							'order_id'              => $order_id,
							'delivery_shipping_num' => $delivery_shipping_num,
							'delivery_company_name' => $delivery_company_name,
							'merchant_id'           => $this->merchant_id,
							'transaction_id'        => $this->get_transaction_id( $order ),
							'rejected'              => 'yes' == pafw_get_meta( $order, '_pafw_escrow_order_confirm_reject' )
						), array (), PAFW()->template_path() );
					} else {
						wc_get_template( 'pafw/escrow.php', array (
							'order'                 => $order,
							'delivery_shipping_num' => $delivery_shipping_num,
							'delivery_company_name' => $delivery_company_name
						), '', PAFW()->template_path() );
					}
				}
			}
		}
		public function my_account_my_orders_actions( $actions, $order ) {
			if ( $this->validate_payment_method_of_order( $order ) && $this->is_refundable( $order, 'mypage' ) ) {

				$cancel_endpoint    = get_permalink( wc_get_page_id( 'cart' ) );
				$myaccount_endpoint = esc_attr( wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) ) );

				$actions['cancel'] = array (
					'url'  => wp_nonce_url( add_query_arg( array (
						'pafw-cancel-order' => 'true',
						'order_key'         => pafw_get_object_property( $order, 'order_key' ),
						'order_id'          => pafw_get_object_property( $order, 'id' ),
						'redirect'          => $myaccount_endpoint
					), $cancel_endpoint ), 'pafw-cancel-order' ),
					'name' => __( 'Cancel', 'woocommerce' )
				);
			} else {
				unset( $actions['cancel'] );
			}

			return $actions;
		}
		public function cancel_order( $order ) {

			if ( $order->get_status() == 'pending' || ( $this->is_vbank( $order ) && $order->get_status() == 'on-hold' ) ) {
				$order->update_status( 'cancelled' );
				wc_add_notice( __( '주문이 정상적으로 취소되었습니다.', 'pgall-for-woocommerce' ), 'success' );

				return;
			}

			if ( ! $this->is_refundable( $order, 'mypage' ) ) {
				wc_add_notice( __( '주문을 취소할 수 없는 상태입니다. 관리자에게 문의해 주세요.', 'pgall-for-woocommerce' ), 'error' );

				return;
			}

			$transaction_id = $this->get_transaction_id( $order );

			if ( ! empty( $transaction_id ) ) {
				try {
					$rst = $this->cancel_request( $order, __( '사용자 주문취소', 'pgall-for-woocommerce' ), __( 'CM_CANCEL_001', 'pgall-for-woocommerce' ) );
					if ( $rst == "success" ) {
						if ( $_POST['refund_request'] ) {
							unset( $_POST['refund_request'] );
						}

						$order->update_status( 'refunded' );
						wc_add_notice( __( '주문이 정상적으로 취소되었습니다.', 'pgall-for-woocommerce' ), 'success' );

						pafw_update_meta_data( $order, '_pafw_order_cancelled', 'yes' );
						pafw_update_meta_data( $order, '_pafw_cancel_date', current_time( 'mysql' ) );

						$this->add_payment_log( $order, '[ 결제 취소 완료 ]', '사용자에 의해 주문이 취소 되었습니다.' );
					} else {
						wc_add_notice( __( '주문 취소 시도중 오류가 발생했습니다. 관리자에게 문의해주세요.', 'pgall-for-woocommerce' ), 'error' );
						$order->add_order_note( sprintf( __( '사용자 주문취소 시도 실패 (에러메세지 : %s)', 'pgall-for-woocommerce' ), $rst ) );
					}
				} catch ( Exception $e ) {
					wc_add_notice( $e->getMessage(), 'error' );
					$order->add_order_note( sprintf( __( '사용자 주문취소 시도 실패 (에러메세지 : %s)', 'pgall-for-woocommerce' ), '결제수단 및 거래번호 없음' ) );
				}
			} else {
				wc_add_notice( __( '주문 취소 시도중 오류 (에러메시지 : 거래번호 없음)가 발생했습니다. 관리자에게 문의해주세요.', 'pgall-for-woocommerce' ), 'error' );
				$order->add_order_note( sprintf( __( '사용자 주문취소 시도 실패 (에러메세지 : %s)', 'pgall-for-woocommerce' ), '결제수단 및 거래번호 없음' ) );
			}
		}
		public function refund_request() {

			$this->check_shop_order_capability();

			$order = $this->get_order();

			if ( ! $this->is_refundable( $order ) ) {
				throw new Exception( __( '주문을 취소할 수 없는 상태입니다.', 'pgall-for-woocommerce' ) );
			}

			$transaction_id = $this->get_transaction_id( $order );

			if ( empty( $transaction_id ) ) {
				throw new Exception( __( '주문 정보에 오류가 있습니다. [ 거래번호 없음 ]', 'pgall-for-woocommerce' ) );
			}

			if ( $this->cancel_request( $order, __( '관리자 주문취소', 'pgall-for-woocommerce' ), __( 'CM_CANCEL_002', 'pgall-for-woocommerce' ) ) ) {
				if ( isset( $_POST['refund_request'] ) ) {
					unset( $_POST['refund_request'] );
				}

				$order->update_status( 'refunded', '관리자에 의해 주문이 취소 되었습니다.' );

				pafw_update_meta_data( $order, '_pafw_order_cancelled', 'yes' );
				pafw_update_meta_data( $order, '_pafw_cancel_date', current_time( 'mysql' ) );

				$this->add_payment_log( $order, '[ 결제 취소 완료 ]', '관리자에 의해 주문이 취소 되었습니다.' );

				wp_send_json_success( __( '주문이 정상적으로 취소되었습니다.', 'pgall-for-woocommerce' ) );
			}

		}
		public function cancel_unpaid_order( $order ) {
			if ( 'on-hold' != $order->get_status() || ! $this->supports( 'pafw-vbank' ) || empty( $this->get_transaction_id( $order ) ) ) {
				return false;
			}
			$vacc_date = pafw_get_meta( $order, '_pafw_vacc_date' );
			$vacc_date = date( 'Ymd235959', strtotime( $vacc_date ) );
			if ( strtotime( $vacc_date ) > strtotime( current_time( 'mysql' ) ) ) {
				return false;
			}

			if ( $this->cancel_request( $order, __( '관리자 주문취소', 'pgall-for-woocommerce' ), __( 'CM_CANCEL_002', 'pgall-for-woocommerce' ) ) ) {

				$order->update_status( 'cancelled', __( '[무통장입금 자동취소] 지불되지 않은 주문이 취소 처리 되었습니다.', 'pgall-for-woocommerce' ) );

				pafw_update_meta_data( $order, '_pafw_order_cancelled', 'yes' );
				pafw_update_meta_data( $order, '_pafw_cancel_date', current_time( 'mysql' ) );

				$this->add_payment_log( $order, '[무통장입금 자동취소 성공]', '지불되지 않은 주문이 취소 처리 되었습니다.' );

				return true;
			} else {
				$this->add_payment_log( $order, '[무통장입금 자동취소 실패]', '지불되지 않은 주문의 취소 처리중 오류가 발생했습니다.', false );

				return false;
			}
		}

		public function woocommerce_email_before_order_table( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order && 'on-hold' == $order->get_status() && $this->id == pafw_get_object_property( $order, 'payment_method' ) && $this->is_vbank( $order ) ) {
				wc_get_template( 'pafw/vbank_acc_info.php', array ( 'order' => $order ), '', PAFW()->template_path() );
			}
		}
		function add_payment_log( $order, $title, $messages = array (), $success = true ) {
			$logs = array ();

			$logs[] = sprintf( '<span class="pafw-log-title %s">%s</span>', $success ? 'success' : 'fail', $title );
			if ( is_array( $messages ) ) {
				foreach ( $messages as $label => $text ) {
					$logs[] = sprintf( '%s : %s', $label, $text );
				}
			} else {
				$logs[] = $messages;
			}

			$log = implode( '<br>', $logs );

			$order->add_order_note( $log );
		}
		function get_cash_receipts( $order ) {
			return '';
		}
		function payment_complete( $order, $tid ) {
			if ( ! $this->is_vbank( $order ) ) {
				if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
					pafw_reduce_order_stock( $order );
				}

				$order->payment_complete( $tid );

				do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );
			}
		}
		function get_transaction_id( $order ) {
			$transaction_id = $order->get_transaction_id();

			if ( empty( $transaction_id ) && $this->is_vbank( $order ) ) {
				$transaction_id = pafw_get_meta( $order, '_pafw_vacc_tid', true );
			}

			return $transaction_id;
		}

		abstract function cancel_request( $order, $msg, $code = "1" );

		static function enqueue_frontend_script() {
		}

		function is_test_key() {
			return false;
		}
		function get_title() {
			$title = parent::get_title();

			if ( ! is_admin() || empty( $_REQUEST['page'] ) || ! in_array( $_REQUEST['page'], array ( 'wc-settings', 'mshop_payment' ) ) ) {
				if ( $this->is_test_key() ) {
					$title = __( '[테스트] ', 'pgall-for-woocommerce' ) . $title;
				}
			}

			return $title;
		}
		function get_description() {
			$description = parent::get_description();

			if ( ! is_admin() || empty( $_REQUEST['page'] ) || ! in_array( $_REQUEST['page'], array ( 'wc-settings', 'mshop_payment' ) ) ) {
				if ( $this->is_test_key() ) {
					$description = __( '<span style="font-size: 0.9em; color: red;">[ 실제 과금이 되지 않거나 자정에 자동으로 취소가 됩니다. ]</span><br>', 'pgall-for-woocommerce' ) . $description;
				}
			}

			return $description;
		}

		function get_receipt_popup_params() {
		}

		function get_merchant_id(){
			return '';
		}
	}
}