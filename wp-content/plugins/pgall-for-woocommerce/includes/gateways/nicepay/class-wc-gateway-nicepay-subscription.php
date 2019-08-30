<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Nicepay_Subscription' ) ) {

		class WC_Gateway_Nicepay_Subscription extends WC_Gateway_Nicepay {

			const PAY_METHOD_BILLKEY = "BILLKEY";
			const PAY_METHOD_BILL = "BILL";
			const ACTION_PAYMENT = "PYO";
			const ACTION_CANCEL = "CLO";

			public function __construct() {
				$this->id = 'nicepay_subscription';

				parent::__construct();

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '나이스페이 정기결제', 'pgall-for-woocommerce' );
					$this->description = __( '나이스페이 정기결제를 진행합니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}

				$this->countries = array ( 'KR' );
				$this->supports  = array (
					'subscriptions',
					'subscription_cancellation',
					'subscription_suspension',
					'subscription_reactivation',
					'subscription_amount_changes',
					'subscription_date_changes',
					'subscription_payment_method_change_customer',
					'subscription_payment_method_change_admin',
					'pafw',
					'pafw_additional_charge',
					'pafw_bill_key_management'
				);
				if ( 'yes' == $this->settings['support_multiple_subscriptions'] ) {
					$this->supports[] = 'multiple_subscriptions';
				}
				if ( 'yes' == $this->settings['support_products'] ) {
					$this->supports[] = 'products';
				}

				add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array ( $this, 'woocommerce_scheduled_subscription_payment' ), 10, 2 );
				add_action( 'woocommerce_subscription_status_cancelled', array ( $this, 'cancel_subscription' ) );
				add_action( 'woocommerce_subscription_cancelled_' . $this->id, array ( $this, 'cancel_subscription' ) );

				add_action( 'woocommerce_subscriptions_pre_update_payment_method', array ( $this, 'maybe_remove_subscription_cancelled_callback' ), 10, 3 );
				add_action( 'woocommerce_subscription_payment_method_updated', array ( $this, 'maybe_reattach_subscription_cancelled_callback' ), 10, 3 );
			}

			function adjust_settings() {
				$this->settings['merchant_id']    = $this->settings['subscription_merchant_id'];
				$this->settings['merchant_key']   = $this->settings['subscription_merchant_key'];
				$this->settings['cancel_pw']      = $this->settings['subscription_cancel_pw'];
				$this->settings['operation_mode'] = $this->settings['operation_mode_subscription'];
				$this->settings['test_user_id']   = $this->settings['test_user_id_subscription'];
			}

			function is_available() {

				if ( parent::is_available() && is_checkout() ) {

					if ( ! in_array( 'products', $this->supports ) && class_exists( 'WC_Subscriptions_Cart' ) && ! WC_Subscriptions_Cart::cart_contains_subscription() && ! isset( $_GET['change_payment_method'] ) && ( ! isset( $_GET['order_id'] ) || ! wcs_order_contains_subscription( $_GET['order_id'] ) ) ) {
						return false;
					}
				}

				return parent::is_available();
			}

			function get_log_dir() {
				$path = PAFW()->plugin_path() . '/lib/nicepay_subscription/log';
				if ( ! file_exists( $path ) || ! is_dir( $path ) ) {
					mkdir( $path );
				}

				return $path;
			}

			public function payment_fields() {
				if ( $this->is_available() ) {
					wp_register_style( 'pafw-nicepay-form-fields', PAFW()->plugin_url() . '/assets/gateways/nicepay/css/form-fields.css' );
					wp_enqueue_style( 'pafw-nicepay-form-fields' );

					$gateway = $this;

					ob_start();
					include( 'templates/form-payment-fields.php' );
					ob_end_flush();
				}
			}

			function check_requirement() {

				parent::check_requirement();

				if ( ! file_exists( PAFW()->plugin_path() . "/lib/nicepay_subscription/lib/NicepayLite.php" ) ) {
					throw new Exception( __( '[ERR-PAFW-0003] NicepayLite.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ) );
				}
			}
			public function get_subscription_bill_key( $order, $is_renewal = false ) {
				$bill_key = '';

				$management_batch_key = pafw_get( $this->settings, 'management_batch_key', 'subscription' );

				if ( 'user' == $management_batch_key ) {
					$bill_key = get_user_meta( $order->get_customer_id(), '_pafw_bill_key' );
				}

				if ( $order instanceof WC_Order && ( empty( $bill_key ) || 'subscription' == $management_batch_key ) ) {

					if ( function_exists( 'wcs_is_subscription' ) && ! wcs_is_subscription( $order ) ) {
						if ( $is_renewal ) {
							$subscriptions = wcs_get_subscriptions_for_renewal_order( pafw_get_object_property( $order, 'id' ) );
						} else {
							$subscriptions = wcs_get_subscriptions_for_order( pafw_get_object_property( $order, 'id' ) );
						}

						if ( ! empty( $subscriptions ) ) {
							$subscription = reset( $subscriptions );
						}
					} else {
						$subscription = $order;
					}

					if ( $subscription ) {
						$bill_key = pafw_get_meta( $subscription, '_pafw_bill_key' );
					}
				}

				return $bill_key;
			}
			public function issue_subscription_bill_key( $order, $payment_info ) {
				$this->check_requirement();

				require_once( PAFW()->plugin_path() . "/lib/nicepay_subscription/lib/NicepayLite.php" );

				$subscriptions = array ();

				$user_id = $order ? pafw_get_object_property( $order, 'customer_id' ) : get_current_user_id();

				if ( 'subscription' == pafw_get( $this->settings, 'management_batch_key', 'subscription' ) ) {
					if ( function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order ) ) {
						$subscriptions = array ( $order );
					} else {
						$subscriptions = wcs_get_subscriptions_for_order( pafw_get_object_property( $order, 'id' ), array ( 'order_type' => 'any' ) );

						if ( empty( $subscriptions ) ) {
							throw new Exception( __( '[오류] 정기결제 관련 정보를 찾을 수 없습니다.', 'pgall-for-woocommerce' ), '1001' );
						}
					}
				}

				$nicepay                = new NicepayLite;
				$nicepay->m_LicenseKey  = pafw_get( $this->settings, 'merchant_key' );
				$nicepay->m_NicepayHome = $this->get_log_dir();
				$nicepay->m_MID         = pafw_get( $this->settings, 'merchant_id' );
				$nicepay->m_PayMethod   = self::PAY_METHOD_BILLKEY;
				$nicepay->m_ssl         = true;
				$nicepay->m_ActionType  = self::ACTION_PAYMENT;
				$nicepay->m_CardNo      = $payment_info['pafw_card_1'] . $payment_info['pafw_card_2'] . $payment_info['pafw_card_3'] . $payment_info['pafw_card_4'];
				$nicepay->m_ExpYear     = substr( $payment_info['pafw_expiry_year'], 2, 2 );
				$nicepay->m_ExpMonth    = $payment_info['pafw_expiry_month'];
				$nicepay->m_IDNo        = $payment_info['pafw_cert_no'];
				$nicepay->m_CardPw      = $payment_info['pafw_card_pw'];
				$nicepay->m_MallIP      = $_SERVER['SERVER_ADDR'];
				$nicepay->m_charSet     = 'UTF8';

				if ( $order ) {
					$nicepay->m_BuyerName  = pafw_get_object_property( $order, 'billing_last_name' ) . pafw_get_object_property( $order, 'billing_first_name' );
					$nicepay->m_BuyerTel   = pafw_get_customer_phone_number( $order );
					$nicepay->m_BuyerEmail = pafw_get_object_property( $order, 'billing_email' );
				} else {
					$nicepay->m_BuyerName  = get_user_meta( get_current_user_id(), 'billing_last_name', true ) . get_user_meta( get_current_user_id(), 'billing_first_name', true );
					$nicepay->m_BuyerTel   = pafw_get_customer_phone_number( null, get_current_user_id() );
					$nicepay->m_BuyerEmail = get_user_meta( get_current_user_id(), 'billing_email', true );
				}

				$nicepay->startAction();

				if ( 'F100' == $nicepay->m_ResultData['ResultCode'] ) {
					foreach ( $subscriptions as $each_subscription ) {
						$this->props = array ();

						pafw_update_meta_data( $each_subscription, '_pafw_payment_method', $this->id );
						pafw_update_meta_data( $each_subscription, '_pafw_auth_date', $nicepay->m_ResultData['AuthDate'] );
						pafw_update_meta_data( $each_subscription, '_pafw_bill_key', $nicepay->m_ResultData['BID'] );
						pafw_update_meta_data( $each_subscription, '_pafw_card_code', $nicepay->m_ResultData['CardCode'] );
						pafw_update_meta_data( $each_subscription, '_pafw_card_name', $nicepay->m_ResultData['CardName'] );
						pafw_update_meta_data( $each_subscription, '_pafw_card_num', $nicepay->m_ResultData['CardNo'] );
					}
					if ( 'user' == pafw_get( $this->settings, 'management_batch_key', 'subscription' ) ) {
						update_user_meta( $user_id, '_pafw_payment_method', $this->id );
						update_user_meta( $user_id, '_pafw_auth_date', $nicepay->m_ResultData['AuthDate'] );
						update_user_meta( $user_id, '_pafw_bill_key', $nicepay->m_ResultData['BID'] );
						update_user_meta( $user_id, '_pafw_card_code', $nicepay->m_ResultData['CardCode'] );
						update_user_meta( $user_id, '_pafw_card_name', $nicepay->m_ResultData['CardName'] );
						update_user_meta( $user_id, '_pafw_card_num', $nicepay->m_ResultData['CardNo'] );
					}

					if ( ! is_null( $order ) ) {
						$this->add_payment_log( $order, '[ 인증키 발급 성공 ]', array (
							'인증키'  => $nicepay->m_ResultData['BID'],
							'카드사'  => $nicepay->m_ResultData['CardName'],
							'카드번호' => $nicepay->m_ResultData['CardNo']
						) );
					}

					return $nicepay->m_ResultData['BID'];
				} else {
					$resultCode = $nicepay->m_ResultData['ResultCode'];
					$resultMsg  = pafw_convert_to_utf8( $nicepay->m_ResultData["ResultMsg"] );

					if ( empty( $resultCode ) && ! empty( $_REQUEST['ResultCode'] ) ) {
						$resultCode = $_REQUEST['ResultCode'];
					}
					if ( empty( $resultMsg ) && ! empty( $_REQUEST['ResultMsg'] ) ) {
						$resultMsg = pafw_convert_to_utf8( $_REQUEST['ResultMsg'] );
					}
					throw new PAFW_Exception( __( sprintf( '인증키 발급 실패 - %s', $resultMsg ), 'pgall-for-woocommerce' ), '1002', $resultCode );
				}
			}
			public function cancel_bill_key( $bill_key ) {
				$this->check_requirement();

				require_once( PAFW()->plugin_path() . "/lib/nicepay_subscription/lib/NicepayLite.php" );

				$nicepay                = new NicepayLite;
				$nicepay->m_LicenseKey  = pafw_get( $this->settings, 'merchant_key' );
				$nicepay->m_NicepayHome = $this->get_log_dir();
				$nicepay->m_MID         = pafw_get( $this->settings, 'merchant_id' );
				$nicepay->m_PayMethod   = self::PAY_METHOD_BILLKEY;
				$nicepay->m_BillKey     = $bill_key;         // 빌키
				$nicepay->m_ssl         = "true";           // 보안접속 여부
				$nicepay->m_ActionType  = "PYO";            // 서비스모드 설정(결제(PY0), 취소(CL0)
				$nicepay->m_debug       = "DEBUG";          // 로그 타입 설정
				$nicepay->m_charSet     = "UTF8";           // 인코딩
				$nicepay->m_CancelFlg   = "1";

				$nicepay->startAction();

				if ( 'F101' == $nicepay->m_ResultData['ResultCode'] ) {
					return true;
				} else {
					throw new PAFW_Exception( __( sprintf( '빌링키 취소 실패 - %s', pafw_convert_to_utf8( $nicepay->m_ResultData["ResultMsg"] ) ), 'pgall-for-woocommerce' ), '1002', $nicepay->m_ResultData['ResultCode'] );
				}
			}
			public function request_subscription_payment( $order, $amount_to_charge, $params = array (), $is_renewal = false, $card_quota = '00', $additional_charge = false ) {
				$this->check_requirement();

				require_once( PAFW()->plugin_path() . "/lib/nicepay_subscription/lib/NicepayLite.php" );

				$bill_key = $this->get_subscription_bill_key( $order, $is_renewal );

				if ( 'yes' == pafw_get( $params, 'nicepay_issue_bill_key' ) || empty( $bill_key ) ) {
					if ( ! $is_renewal && ! empty( $params ) ) {
						$bill_key = $this->issue_subscription_bill_key( $order, $params );
					} else {
						throw new Exception( __( '빌키 정보가 없습니다.', 'pgall-for-woocommerce' ), '5001' );
					}
				}

				if ( $amount_to_charge > 0 ) {
					$nicepay                 = new NicepayLite;
					$nicepay->m_MID          = pafw_get( $this->settings, 'merchant_id' );
					$nicepay->m_LicenseKey   = pafw_get( $this->settings, 'merchant_key' );
					$nicepay->m_NicepayHome  = $this->get_log_dir();
					$nicepay->m_ssl          = true;
					$nicepay->m_ActionType   = self::ACTION_PAYMENT;
					$nicepay->m_NetCancelPW  = pafw_get( $this->settings, 'cancel_pw' );
					$nicepay->m_debug        = pafw_get( $this->settings, 'debug' );
					$nicepay->m_PayMethod    = self::PAY_METHOD_BILL;
					$nicepay->m_charSet      = 'UTF8';
					$nicepay->m_MallIP       = $_SERVER['SERVER_ADDR'];
					$nicepay->m_BillKey      = $bill_key;
					$nicepay->m_BuyerName    = pafw_get_object_property( $order, 'billing_last_name' ) . pafw_get_object_property( $order, 'billing_first_name' );
					$nicepay->m_Amt          = $amount_to_charge;
					$nicepay->m_Moid         = pafw_get_object_property( $order, 'id' );
					$nicepay->m_GoodsName    = $this->make_product_info( $order );
					$nicepay->m_CardQuota    = $card_quota;
					$nicepay->m_NetCancelAmt = $amount_to_charge;

					$nicepay->startAction();

					if ( '3001' == $nicepay->m_ResultData['ResultCode'] ) {
						if ( ! $additional_charge ) {
							$this->props = array ();

							pafw_update_meta_data( $order, '_pafw_payment_method', $this->id );
							pafw_update_meta_data( $order, '_pafw_auth_date', $nicepay->m_ResultData['AuthDate'] );
							pafw_update_meta_data( $order, '_pafw_bill_key', $nicepay->m_ResultData['BID'] );
							pafw_update_meta_data( $order, '_pafw_card_code', $nicepay->m_ResultData['CardCode'] );
							pafw_update_meta_data( $order, '_pafw_card_name', $nicepay->m_ResultData['CardName'] );
							pafw_update_meta_data( $order, '_pafw_card_num', $nicepay->m_ResultData['CardNo'] );
							pafw_update_meta_data( $order, '_pafw_card_quota', $nicepay->m_ResultData['CardQuota'] );
							pafw_update_meta_data( $order, "_pafw_total_price", $amount_to_charge );
							pafw_update_meta_data( $order, "_pafw_txnid", $nicepay->m_ResultData['TID'] );

							if ( ! is_null( $order ) ) {
								$this->add_payment_log( $order, '[ 정기결제 성공 ]', array (
									'거래번호' => $nicepay->m_ResultData['TID'],
									'승인번호' => $nicepay->m_ResultData['AuthCode']
								) );

								$this->payment_complete( $order, $nicepay->m_ResultData['TID'] );
							}
						}else{
							do_action( 'pafw_payment_action', 'completed', $amount_to_charge, $order, $this );
						}

						return $nicepay;
					} else {
						$message = sprintf( '정기결제 실패 : %s', $nicepay->m_ResultData['ResultMsg'] );
						throw new PAFW_Exception( $message, '1003', $nicepay->m_ResultData['ResultCode'] );
					}
				} else {
					if ( function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order ) ) {
						return null;
					}

					$this->payment_complete( $order, '' );
				}
			}

			function process_payment( $order_id ) {
				$order = wc_get_order( $order_id );

				do_action( 'pafw_process_payment', $order );

				return $this->process_subscription_payment( $order_id, pafw_get_object_property( $order, 'order_key' ) );
			}

			function process_order_pay() {
				$params = array ();
				parse_str( $_REQUEST['data'], $params );

				$_REQUEST = array_merge( $_REQUEST, $params );

				$result = $this->process_subscription_payment( $_REQUEST['order_id'], $_REQUEST['order_key'] );

				if ( $result ) {
					wp_send_json_success( $result );
				} else {
					$message = wc_get_notices( 'error' );
					wc_clear_notices();
					wp_send_json_error( implode( "\n", $message ) );
				}
			}

			public function process_subscription_payment( $order_id, $order_key ) {
				try {
					$order = $this->get_order( $order_id, $order_key );

					if ( function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order ) ) {

						remove_action( 'woocommerce_subscription_status_cancelled', array ( $this, 'cancel_subscription' ) );
						remove_action( 'woocommerce_subscription_cancelled_' . $this->id, array ( $this, 'cancel_subscription' ) );

						$params = array ();
						parse_str( $_REQUEST['data'], $params );
						if ( $this->id == $order->get_payment_method() ) {
							try {
								$bill_key = pafw_get_meta( $order, '_pafw_bill_key' );

								if ( ! empty( $bill_key ) ) {
									$this->cancel_bill_key( $bill_key );

									$this->add_payment_log( $order, '[ 정기결제 빌링키 삭제 성공 ]', array (
										'정기결제 빌링키' => $bill_key
									) );
								}
							}catch (Exception $e ) {

							}
						}

						$this->request_subscription_payment( $order, 0, $params );

						WC_Subscriptions_Change_Payment_Gateway::update_payment_method( $order, $this->id );

						$this->add_payment_log( $order, '[ 결제 수단 변경 ]', array (
							'결제수단' => $this->title
						) );

						return array (
							'result'       => 'success',
							'redirect_url' => $order->get_view_order_url()
						);
					} else {
						pafw_set_browser_information( $order );
						$this->has_enough_stock( $order );
						$order->set_payment_method( $this );

						if ( is_callable( $order, 'save' ) ) {
							$order->save();
						}

						$this->request_subscription_payment( $order, $order->get_total(), $_REQUEST );

						return array (
							'result'       => 'success',
							'redirect_url' => $order->get_checkout_order_received_url()
						);
					}

				} catch ( Exception $e ) {
					$error_code = '';
					if ( $e instanceof PAFW_Exception ) {
						$error_code = $e->getErrorCode();
					}

					$message = sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() );
					$this->add_log( "[오류] " . $message . "\n" . print_r( $_REQUEST, true ) );

					if ( $order ) {
						$order->add_order_note( $message );
						if ( empty( pafw_get_object_property( $order, 'paid_date' ) ) ) {
							$order->update_status( 'failed', __( '나이스페이 결제내역을 확인하신 후, 고객에게 연락을 해주시기 바랍니다.', 'pgall-for-woocommerce' ) );
						}
					}

					do_action( 'pafw_payment_fail', $order, ! empty( $error_code ) ? $error_code : $e->getCode(), $e->getMessage() );

					wc_add_notice( $message, 'error' );
				}
			}

			function woocommerce_scheduled_subscription_payment( $amount_to_charge, $order ) {
				try {
					$this->request_subscription_payment( $order, $amount_to_charge, array (), true );
				} catch ( Exception $e ) {
					$message = sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() );
					$order->update_status( 'failed', $message );
				}
			}
			public function cancel_request( $order, $msg, $code = "1" ) {

				$this->check_requirement();

				require_once( PAFW()->plugin_path() . "/lib/nicepay_subscription/lib/NicepayLite.php" );

				$transaction_id = $this->get_transaction_id( $order );

				$nicepay                = new NicepayLite;
				$nicepay->m_MID         = pafw_get( $this->settings, 'merchant_id' );
				$nicepay->m_LicenseKey  = pafw_get( $this->settings, 'merchant_key' );
				$nicepay->m_NicepayHome = $this->get_log_dir();
				$nicepay->m_ssl         = true;
				$nicepay->m_TID         = $transaction_id;
				$nicepay->m_CancelAmt   = $order->get_total();
				$nicepay->m_CancelMsg   = $msg;
				$nicepay->m_CancelPwd   = pafw_get( $this->settings, 'cancel_pw' );
				$nicepay->m_ActionType  = self::ACTION_CANCEL;
				$nicepay->m_MallIP      = $_SERVER['SERVER_ADDR'];
				$nicepay->m_charSet     = 'UTF8';

				$nicepay->startAction();

				if ( '2001' == $nicepay->m_ResultData['ResultCode'] ) {

					if ( class_exists( 'WC_Subscriptions_Manager' ) ) {
						WC_Subscriptions_Manager::cancel_subscriptions_for_order( $order );
					}

					do_action( 'pafw_payment_action', 'cancelled', $order->get_total(), $order, $this );

					return true;
				} else {
					throw new Exception( sprintf( '주문취소중 오류가 발생했습니다. [%s] %s', $nicepay->m_ResultData['ResultCode'], $nicepay->m_ResultData['ResultMsg'] ) );
				}
			}

			public function subscription_additional_charge() {
				try {
					check_ajax_referer( 'pgall-for-woocommerce' );

					if ( ! current_user_can( 'publish_shop_orders' ) ) {
						throw new Exception( __( '주문 관리 권한이 없습니다.', 'pgall-for-woocommerce' ) );
					}

					$order = wc_get_order( $_REQUEST['order_id'] );

					if ( $order ) {
						$amount_to_charge = $_REQUEST['amount'];
						$card_quota       = pafw_get( $_REQUEST, 'card_quota', '00' );

						$nicepay = $this->request_subscription_payment( $order, $amount_to_charge, array (), function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order ), $card_quota, true );
						$history = pafw_get_meta( $order, '_pafw_additional_charge_history' );
						if ( empty( $history ) ) {
							$history = array ();
						}

						$history[ $nicepay->m_ResultData['TID'] ] = array (
							'status'         => 'PAYED',
							'auth_date'      => '20' . $nicepay->m_ResultData['AuthDate'],
							'charged_amount' => $amount_to_charge
						);;

						pafw_update_meta_data( $order, '_pafw_additional_charge_history', $history );

						$this->add_payment_log( $order, '[ 추가 과금 성공 ]', array (
							'거래요청번호' => $nicepay->m_ResultData['TID'],
							'추가과금금액' => wc_price( $amount_to_charge )
						) );

						wp_send_json_success( '추가 과금 요청이 정상적으로 처리되었습니다.' );
					} else {
						throw new Exception( __( '주문 정보를 찾을 수 없습니다.', 'pgall-for-woocommerce' ), '5002' );
					}
				} catch ( Exception $e ) {
					wp_send_json_error( sprintf( __( '[ 추가과금실패 ][PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() ) );
				}
			}
			public function do_repay( $order, $amount, $already_refunded = false ) {

				$this->check_requirement();

				require_once( PAFW()->plugin_path() . "/lib/nicepay_subscription/lib/NicepayLite.php" );

				$transaction_id = $this->get_transaction_id( $order );

				$nicepay                      = new NicepayLite;
				$nicepay->m_MID               = pafw_get( $this->settings, 'merchant_id' );
				$nicepay->m_LicenseKey        = pafw_get( $this->settings, 'merchant_key' );
				$nicepay->m_NicepayHome       = $this->get_log_dir();
				$nicepay->m_ssl               = true;
				$nicepay->m_TID               = $transaction_id;
				$nicepay->m_CancelAmt         = $amount;
				$nicepay->m_CancelMsg         = __( '관리자 요청에 의한 부분환불', 'pgall-for-woocommerce' );
				$nicepay->m_CancelPwd         = pafw_get( $this->settings, 'cancel_pw' );
				$nicepay->m_ActionType        = self::ACTION_CANCEL;
				$nicepay->m_MallIP            = $_SERVER['SERVER_ADDR'];
				$nicepay->m_charSet           = 'UTF8';
				$nicepay->m_PartialCancelCode = 1;

				$nicepay->startAction();

				if ( '2001' == $nicepay->m_ResultData['ResultCode'] ) {

					do_action( 'pafw_payment_action', 'cancelled', $amount, $order, $this );

					$order_id       = pafw_get_object_property( $order, 'id' );
					$transaction_id = $this->get_transaction_id( $order );

					$confirm_price = ( ( $order->get_total() - $order->get_total_refunded() ) - $amount );    //재승인 요청금액(기존승인금액 - 취소금액)
					if ( $already_refunded ) {
						$confirm_price += $amount;
					}

					$refund_reason = __( '관리자 요청에 의한 부분환불', 'pgall-for-woocommerce' );

					if ( ! $already_refunded ) {
						//부분 환불 처리
						$refund = wc_create_refund( array (
							'amount'     => $amount,
							'reason'     => $refund_reason,
							'order_id'   => $order_id,
							'line_items' => array (),
						) );
					}

					//부분환불 정보 확인
					$nicepay_repay = pafw_get_meta( $order, '_pafw_repay' );
					$nicepay_repay = json_decode( $nicepay_repay, true );

					if ( ! is_array( $nicepay_repay ) ) {
						$nicepay_repay = array ();
					}

					$repay_cnt = count( $nicepay_repay ) + 1;

					$nicepay_repay[ $repay_cnt ] = array (
						'newtid'       => $nicepay->m_ResultData['TID'],
						'result_code'  => $nicepay->m_ResultData['ResultCode'],
						'result_msg'   => $nicepay->m_ResultData['ResultMsg'],
						'refund_price' => $nicepay->m_ResultData['CancelAmt'],
						'cancel_date'  => $nicepay->m_ResultData['CancelDate'],
						'cancel_time'  => $nicepay->m_ResultData['CancelTime'],
						'cancel_num'   => $nicepay->m_ResultData['CancelNum']
					);

					$this->add_payment_log( $order, '[ 부분 취소 성공 ]', array (
						'나이스페이 거래번호' => $nicepay->m_ResultData['TID'],
						'부분취소 금액'    => number_format( $nicepay->m_ResultData['CancelAmt'] )
					) );

					pafw_update_meta_data( $order, '_pafw_repay', json_encode( $nicepay_repay, JSON_UNESCAPED_UNICODE ) );

					//부분취소후 재승인 금액이 0원인 경우 모든 금액을 부분환불 처리한 것으로 이경우 환불됨 상태로 변경처리.
					if ( $confirm_price == 0 ) {
						$order->update_status( 'refunded' );
						pafw_update_meta_data( $order, '_pafw_order_cancelled', 'yes' );
						pafw_update_meta_data( $order, '_pafw_cancel_date', current_time( 'mysql' ) );

						if ( class_exists( 'WC_Subscriptions_Manager' ) ) {
							WC_Subscriptions_Manager::cancel_subscriptions_for_order( $order );
						}
					}

					return true;
				} else {
					throw new Exception( sprintf( '주문취소중 오류가 발생했습니다. [%s] %s', $nicepay->m_ResultData['ResultCode'], $nicepay->m_ResultData['ResultMsg'] ) );
				}
			}
			function repay_request() {
				if ( ! current_user_can( 'publish_shop_orders' ) ) {
					throw new Exception( __( '주문 관리 권한이 없습니다.', 'pgall-for-woocommerce' ) );
				}

				$order = $this->get_order();

				$amount = isset( $_REQUEST['amount'] ) ? intval( $_REQUEST['amount'] ) : '';

				if ( $amount <= 0 ) {
					throw new Exception( __( '환불 금액은 0보다 커야합니다.', 'pgall-for-woocommerce' ) );
				}

				//부분취소 요청
				$this->do_repay( $order, $amount );

				wp_send_json_success( __( '부분환불이 정상적으로 처리되었습니다. 주문 메모 내용을 확인해 주세요.', 'pgall-for-woocommerce' ) );
			}
			function add_meta_box_repay( $post ) {
				$order = wc_get_order( $post );

				$repay_info     = pafw_get_meta( $order, '_pafw_repay' );
				$repay_cnt      = count( json_decode( $repay_info, true ) );
				$card_code      = pafw_get_meta( $order, '_pafw_card_code' );
				$card_bank_code = pafw_get_meta( $order, '_pafw_card_bank_code' );
				$can_repay      = true;

				include_once( 'views/repay.php' );
			}
			public function add_meta_boxes( $order ) {
				parent::add_meta_boxes( $order );

				if ( $this->supports( 'pafw_additional_charge' ) ) {
					add_meta_box(
						'pafw-order-additional-charge',
						__( '나이스페이 추가과금 <span class="pafw-powerd"><a target="_blank" href="https://www.codemshop.com/">Powered by CodeM</a></span>', 'pgall-for-woocommerce' ),
						array ( $this, 'add_meta_box_additional_charge' ),
						'shop_order',
						'side',
						'high'
					);
				}

				if ( $this->is_refundable( $order ) && ! in_array( $order->get_status(), apply_filters( 'woocommerce_valid_order_statuses_for_payment', array ( 'on-hold', 'pending', 'failed' ), $order ) ) ) {
					add_meta_box(
						'pafw-order-repay',
						__( '신용카드 부분환불 <span class="pafw-powerd"><a target="_blank" href="https://www.codemshop.com/">Powered by CodeM</a></span>', 'pgall-for-woocommerce' ),
						array ( $this, 'add_meta_box_repay' ),
						'shop_order',
						'side',
						'high'
					);
				}
			}

			public function subscription_cancel_additional_charge() {
				check_ajax_referer( 'pgall-for-woocommerce' );

				if ( ! current_user_can( 'publish_shop_orders' ) ) {
					throw new Exception( __( '주문 관리 권한이 없습니다.', 'pgall-for-woocommerce' ) );
				}

				require_once( PAFW()->plugin_path() . "/lib/nicepay_subscription/lib/NicepayLite.php" );

				$order = wc_get_order( $_REQUEST['order_id'] );

				$nicepay                = new NicepayLite;
				$nicepay->m_MID         = pafw_get( $this->settings, 'merchant_id' );
				$nicepay->m_LicenseKey  = pafw_get( $this->settings, 'merchant_key' );
				$nicepay->m_NicepayHome = $this->get_log_dir();
				$nicepay->m_ssl         = true;
				$nicepay->m_TID         = $_REQUEST['tid'];
				$nicepay->m_CancelAmt   = $_REQUEST['amount'];
				$nicepay->m_CancelMsg   = __( '추가과금취소', 'pgall-for-woocommerce' );
				$nicepay->m_CancelPwd   = pafw_get( $this->settings, 'cancel_pw' );
				$nicepay->m_ActionType  = self::ACTION_CANCEL;
				$nicepay->m_MallIP      = $_SERVER['SERVER_ADDR'];
				$nicepay->m_charSet     = 'UTF8';

				$nicepay->startAction();

				if ( '2001' == $nicepay->m_ResultData['ResultCode'] ) {

					do_action( 'pafw_payment_action', 'cancelled', $_REQUEST['amount'], $order, $this );

					$this->add_payment_log( $order, '[ 추가 과금 취소 성공 ]', array (
						'거래요청번호' => $_REQUEST['tid'],
						'취소금액'   => wc_price( $_REQUEST['amount'] )
					) );

					$history = pafw_get_meta( $order, '_pafw_additional_charge_history' );

					$history[ $_REQUEST['tid'] ]['status'] = 'CANCELED';

					pafw_update_meta_data( $order, '_pafw_additional_charge_history', $history );

					wp_send_json_success( '추가 과금 취소 요청이 정상적으로 처리되었습니다.' );
				} else {
					throw new Exception( sprintf( '추가 과금 취소중 오류가 발생했습니다. [%s] %s', $nicepay->m_ResultData['ResultCode'], $nicepay->m_ResultData['ResultMsg'] ) );
				}
			}

			public function register_card() {
				check_ajax_referer( 'pgall-for-woocommerce' );

				try {
					if ( isset( $_REQUEST['data'] ) ) {
						$payment_info = array ();
						parse_str( $_REQUEST['data'], $payment_info );
						do_action( 'pafw_before_register_card', $payment_info );

						if ( $this->issue_subscription_bill_key( null, $payment_info ) ) {
							wp_send_json_success();
						}
					} else {
						throw new Exception( __( '잘못된 요청입니다.', 'pgall-for-woocommerce' ) );
					}
				} catch ( Exception $e ) {
					wp_send_json_error( $e->getMessage() );
				}
			}

			public function delete_card() {
				check_ajax_referer( 'pgall-for-woocommerce' );

				try {
					$user_id  = get_current_user_id();
					$bill_key = get_user_meta( $user_id, '_pafw_bill_key' );

					if ( ! empty( $bill_key ) ) {
						$this->cancel_bill_key( $bill_key );

						delete_user_meta( $user_id, '_pafw_payment_method' );
						delete_user_meta( $user_id, '_pafw_auth_date' );
						delete_user_meta( $user_id, '_pafw_bill_key' );
						delete_user_meta( $user_id, '_pafw_card_code' );
						delete_user_meta( $user_id, '_pafw_card_name' );
						delete_user_meta( $user_id, '_pafw_card_num' );
					} else {
						throw new Exception( __( '빌링키 정보가 없습니다.', 'pgall-for-woocommerce' ) );
					}

					wp_send_json_success();
				} catch ( Exception $e ) {
					wp_send_json_error( $e->getMessage() );
				}
			}
			function cancel_subscription( $subscription ) {
				if ( 'subscription' == pafw_get( $this->settings, 'management_batch_key', 'subscription' ) && $subscription && $subscription->get_payment_method() == $this->id ) {
					try {
						$bill_key = pafw_get_meta( $subscription, '_pafw_bill_key' );

						if ( ! empty( $bill_key ) ) {
							$this->cancel_bill_key( $bill_key );

							$this->add_payment_log( $subscription, '[ 정기결제 빌링키 삭제 성공 ]', array (
								'정기결제 빌링키' => $bill_key
							) );
						} else {
							throw new Exception( '빌링키 정보가 없습니다.' );
						}
					} catch ( Exception $e ) {
						$this->add_payment_log( $subscription, '[ 정기결제 빌링키 삭제 오류 ]', array (
							'CODE' => $e->getCode(),
							'MSG'  => $e->getMessage()
						) );
					}
				}
			}
			public function maybe_remove_subscription_cancelled_callback( $subscription, $new_payment_method, $old_payment_method ) {
				if ( $this->id == $new_payment_method && $this->id == $old_payment_method ) {
					$subscription->add_order_note( __( 'Detach Cancelled Callback', 'pgall-for-woocommerce' ) );
					remove_action( 'woocommerce_subscription_cancelled_' . $this->id, array ( $this, 'cancel_subscription' ) );
				}
			}
			public function maybe_reattach_subscription_cancelled_callback( $subscription, $new_payment_method, $old_payment_method ) {
				if ( $this->id == $new_payment_method && $this->id == $old_payment_method ) {
					$subscription->add_order_note( __( 'Reattach Cancelled Callback', 'pgall-for-woocommerce' ) );
					add_action( 'woocommerce_subscription_cancelled_' . $this->id, array ( $this, 'cancel_subscription' ) );
				}
			}
			public function subscription_cancel_batch_key() {
				if ( ! current_user_can( 'publish_shop_orders' ) ) {
					throw new Exception( __( '[ERR-0000003] 잘못된 요청입니다.', 'pgall-for-woocommerce' ) );
				}

				if ( empty( $_POST['subscription_id'] ) || empty( $_REQUEST['batch_key'] ) ) {
					throw new Exception( __( '[ERR-0000001] 잘못된 요청입니다.', 'pgall-for-woocommerce' ) );
				}

				$subscription = wcs_get_subscription( $_POST['subscription_id'] );
				if ( empty( $subscription ) || $this->id !== $subscription->get_payment_method() ) {
					throw new Exception( __( '[ERR-0000002] 잘못된 요청입니다.', 'pgall-for-woocommerce' ) );
				}

				$this->cancel_bill_key( $_REQUEST['batch_key'] );

				$this->add_payment_log( $subscription, '[ 정기결제 빌링키 삭제 성공 ]', array (
					'정기결제 빌링키' => $_REQUEST['batch_key']
				) );

				wp_send_json_success( __( '정기결제 배치키 비활성화가 정상적으로 처리되었습니다.' ) );
			}
		}

	}
}
