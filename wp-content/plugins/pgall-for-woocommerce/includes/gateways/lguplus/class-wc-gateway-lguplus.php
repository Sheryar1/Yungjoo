<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {
	class WC_Gateway_Lguplus extends PAFW_Payment_Gateway {

		public static $log;

		protected $config_path;

		protected $key_for_test = array (
			'lgdacomxpay',
			'tlgdacomxpay'
		);
		public function __construct() {
			$this->view_transaction_url = '';
			$this->config_path = PAFW()->plugin_path() . '/lib/lguplus/lgdacom' . ( wp_is_mobile() ? '_mobile' : '' );

			$this->master_id = 'lguplus';
			$this->pg_title     = __( 'LG유플러스', 'pgall-for-woocommerce' );
			$this->method_title = __( 'LG유플러스', 'pgall-for-woocommerce' );

			parent::__construct();

			add_action( 'pafw_payment_info_meta_box_action_button_' . $this->id, array ( $this, 'add_meta_box_action_button' ) );
		}
		function check_requirement() {

			parent::check_requirement();

			if ( ! file_exists( PAFW()->plugin_path() . "/lib/lguplus/lgdacom/XPayClient.php" ) ) {
				throw new Exception( __( '[ERR-PAFW-0003] XPayClient.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ) );
			}
		}

		public static function add_script_params( $params ) {
			$options = get_option( 'pafw_mshop_lguplus' );

			$params['lguplus_mode'] = 'production' == pafw_get( $options, 'operation_mode', 'sandbox' ) ? 'service' : 'test';

			return $params;
		}
		public static function enqueue_frontend_script() {
			?>
            <script language="javascript" src="https://xpay.uplus.co.kr/xpay/js/xpay_crossplatform.js" type="text/javascript"></script>
			<?php
		}

		function process_order_pay() {
			wp_send_json_success( $this->get_payment_form() );
		}

		function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			do_action( 'pafw_process_payment', $order );

			return $this->get_payment_form( $order_id, pafw_get_object_property( $order, 'order_key' ) );
		}
		function get_payment_form( $order_id, $order_key ) {
			try {
				$this->check_requirement();

				require_once( PAFW()->plugin_path() . '/lib/lguplus/lgdacom/XPayClient.php' );

				$order = $this->get_order( $order_id, $order_key );

				pafw_set_browser_information( $order );
				$this->has_enough_stock( $order );
				$order->set_payment_method( $this );

				if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
					$order->save();
				}

				$CST_PLATFORM    = 'production' == $this->operation_mode ? 'service' : 'test';
				$CST_MID         = $this->merchant_id;
				$LGD_MID         = ( 'sandbox' == $this->operation_mode ? 't' : '' ) . $this->merchant_id;
				$LGD_OID         = $this->get_txnid( $order );
				$LGD_AMOUNT      = $order->get_total();
				$LGD_TIMESTAMP   = date( 'YmdHis' );                         //타임스탬프
				$LGD_BUYER       = pafw_get_object_property( $order, 'billing_last_name' ) . pafw_get_object_property( $order, 'billing_first_name' );
				$LGD_PRODUCTINFO = $this->make_product_info( $order );;
				$LGD_BUYEREMAIL = pafw_get_object_property( $order, 'billing_email' );
				$LGD_RETURNURL  = $this->get_api_url( wp_is_mobile() ? 'payment' : 'return' );
				$LGD_CASNOTEURL = $this->get_api_url( 'cancel' );

				$xpay = new XPayClient( $this->config_path, $CST_PLATFORM );
				$xpay->Init_TX( $LGD_MID );
				$LGD_HASHDATA = md5( $LGD_MID . $LGD_OID . $LGD_AMOUNT . $LGD_TIMESTAMP . $this->merchant_key );

				ob_start();
				include( 'templates/payment_form' . ( wp_is_mobile() ? '_mobile' : '' ) . '.php' );
				$form_tag = ob_get_clean();

				return array (
					'result'       => 'success',
					'payment_form' => '<div data-id="mshop-payment-form" style="display:none">' . $form_tag . '</div>'
				);
			} catch ( Exception $e ) {
				wc_add_notice( $e->getMessage(), 'error' );
			}
		}
		function cancel_request( $order, $tid, $msg = '취소요청' ) {
			$this->check_requirement();

			require_once( PAFW()->plugin_path() . '/lib/lguplus/lgdacom/XPayClient.php' );

			$CST_PLATFORM = 'production' == $this->operation_mode ? 'service' : 'test';
			$LGD_MID      = ( 'sandbox' == $this->operation_mode ? 't' : '' ) . $this->merchant_id;
			$LGD_TID      = $this->get_transaction_id( $order );

			$xpay                     = new XPayClient( $this->config_path, $CST_PLATFORM );
			$xpay->config[ $LGD_MID ] = $this->merchant_key;

			if ( ! $xpay->Init_TX( $LGD_MID ) ) {
				throw new PAFW_Exception( __( '결제 취소중 오류가 발생했습니다.', 'pgall-for-woocommerce' ), '5002' );
			}

			$xpay->Set( "LGD_TXNAME", "Cancel" );
			$xpay->Set( "LGD_TID", $LGD_TID );

			if ( $xpay->TX() ) {
				$success_codes = array ( '0000', 'RF10', ' RF09', ' RF15', ' RF19', ' RF23', ' RF25' );

				if ( in_array( $xpay->Response_Code(), $success_codes ) ) {
					do_action( 'pafw_payment_action', 'cancelled', $order->get_total(), $order, $this );

					return 'success';
				} else {
					throw new PAFW_Exception( mb_convert_encoding( $xpay->Response_Msg(), "UTF-8", "EUC-KR" ), '5001', $xpay->Response_Code() );
				}
			} else {
				throw new PAFW_Exception( mb_convert_encoding( $xpay->Response_Msg(), "UTF-8", "EUC-KR" ), '5001', $xpay->Response_Code() );
			}
		}
		public function lguplus_mypage_cancel_order( $order_id ) {
			$order = wc_get_order( $order_id );

			$valid_order_status = $this->settings['possible_refund_status_for_mypage'];

			if ( $order->get_status() == 'pending' ) {
				$order->update_status( 'cancelled' );
				wc_add_notice( __( '주문이 정상적으로 취소되었습니다.', 'pgall-for-woocommerce' ), 'success' );

				return;
			}

			if ( ! in_array( $order->get_status(), $valid_order_status ) ) {
				wc_add_notice( __( '주문을 취소할 수 없는 상태입니다. 관리자에게 문의해 주세요.', 'pgall-for-woocommerce' ), 'error' );

				return;
			}

			$paymethod     = get_post_meta( $order_id, "_payment_method", true );
			$paymethod     = strtolower( $paymethod );
			$paymethod_tid = get_post_meta( $order_id, "_transaction_id", true );

			if ( ! empty( $paymethod ) || ! empty( $paymethod_tid ) ) {

				//가상계좌 취소 처리
				if ( $paymethod == 'lguplus_vbank' && $order->get_status() == 'on-hold' ) {

					$order->update_status( 'cancelled' );
					wc_add_notice( __( '주문이 정상적으로 취소되었습니다.', 'pgall-for-woocommerce' ), 'success' );

					return;

				} else {

					//결제 취소 요청 처리
					$result_data = $this->cancel_order( $order_id, $paymethod_tid, __( '사용자 주문취소', 'pgall-for-woocommerce' ) );

					//처리 결과 배열을 변수로 나누어 저장처리
					extract( $result_data );

					if ( $resultCode == '2001' ) {   // 취소성공

						$order->update_status( 'refunded' );
						update_post_meta( pafw_get_object_property( $order, 'id' ), '_pafw_order_cancelled', true );

						$order->add_order_note(
							sprintf( __( '사용자의 요청으로 주문(#%s)이 취소처리 되었습니다.<br><hr>결과 코드 : %s<br>결과 메시지 : %s<br>취소금액 : %s<br>취소일 : %s<br>취소시간 : %s<br>취소번호 : %s<br>취소결제수단 : %s<br>상점ID : %s<br>취소거래ID : %s<br>', 'pgall-for-woocommerce' ),
								$order_id, $resultCode, $resultMsg, $cancelAmt, $cancelDate, $cancelTime, $cancelNum, $payMethod, $mid, $tid )
						);

						wc_add_notice( __( '주문이 정상적으로 취소되었습니다. 취소 결과를 확인해주세요.', 'pgall-for-woocommerce' ), 'success' );

					} else if ( $resultCode == '2002' ) {   //취소진행중

						$order->add_order_note(
							sprintf( __( '사용자의 요청으로 주문(#%s)취소를 진행하였으나 취소 진행중으로 확인됩니다. 주문을 다시 한번 확인해주세요.<br><hr>결과 코드 : %s<br>결과 메시지 : %s<br>취소금액 : %s<br>취소일 : %s<br>취소시간 : %s<br>취소번호 : %s<br>취소결제수단 : %s<br>상점ID : %s<br>취소거래ID : %s<br>', 'pgall-for-woocommerce' ),
								$order_id, $resultCode, $resultMsg, $cancelAmt, $cancelDate, $cancelTime, $cancelNum, $payMethod, $mid, $tid )
						);

						wc_add_notice( sprintf( __( "주문 취소가 진행 중인 것으로 확인됩니다. 잠시 후 다시 한번 확인 부탁드립니다. 에러코드 : %s, 에러메시지 : %s", 'pgall-for-woocommerce' ), $resultCode, $resultMsg ), 'success' );

					} else {    // 실패
						$order->add_order_note(
							sprintf( __( '사용자의 요청으로 주문(#%s)취소를 진행하였으나 다음과 같은 사유로 실패하였습니다. 결과메시지를 확인해주세요.<br><hr>결과 코드 : %s<br>결과 메시지 : %s<br>취소금액 : %s<br>취소일 : %s<br>취소시간 : %s<br>취소번호 : %s<br>취소결제수단 : %s<br>상점ID : %s<br>취소거래ID : %s<br>', 'pgall-for-woocommerce' ),
								$order_id, $resultCode, $resultMsg, $cancelAmt, $cancelDate, $cancelTime, $cancelNum, $payMethod, $mid, $tid )
						);

						wc_add_notice( sprintf( __( "주문 취소 시도중 오류가 발생했습니다. 에러코드 : %s, 에러메시지 : %s", 'pgall-for-woocommerce' ), $resultCode, $resultMsg ), 'error' );
					}
				}
			} else {
				wc_add_notice( __( "주문 취소 시도중 오류가 발생했습니다. 에러메시지 : 결제수단 및 거래번호 없음", 'pgall-for-woocommerce' ), 'error' );
				$order->add_order_note( __( '사용자 주문취소 시도 실패. 에러메세지 : 결제수단 및 거래번호 없음', 'pgall-for-woocommerce' ) );
			}
		}
		public function woocommerce_my_account_my_orders_actions( $actions, $order ) {
			$payment_method = get_post_meta( pafw_get_object_property( $order, 'id' ), '_payment_method', true );

			if ( $payment_method == $this->id ) {
				$valid_order_status = $this->settings['possible_refund_status_for_mypage'];

				if ( ! empty( $valid_order_status ) && $valid_order_status != '-1' && in_array( $order->get_status(), $valid_order_status ) ) {

					$cancel_endpoint    = get_permalink( wc_get_page_id( 'cart' ) );
					$myaccount_endpoint = esc_attr( wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) ) );
					$paymethod_tid      = get_post_meta( pafw_get_object_property( $order, 'id' ), "_transaction_id", true );

					//결제 수단과 TID(거래번호)가 없는 경우 사용자 내계정 페이지에서 취소버튼 미노출 처리 추가
					if ( ! empty( $payment_method ) || ! empty( $paymethod_tid ) ) {

						$actions['cancel'] = array (
							'url'  => wp_nonce_url( add_query_arg( array (
								'lguplus-cancel-order' => 'true',
								'order'                => pafw_get_object_property( $order, 'order_key' ),
								'order_id'             => pafw_get_object_property( $order, 'id' ),
								'redirect'             => $myaccount_endpoint
							), $cancel_endpoint ), 'lguplus-cancel-order' ),
							'name' => __( 'Cancel', 'woocommerce' )
						);
					}
				} else {
					unset( $actions['cancel'] );
				}
			}

			return $actions;
		}
		function do_payment() {
			try {
				if ( empty( $_REQUEST['LGD_OID'] ) ) {
					throw new PAFW_Exception( __( '필수 파라미터가 누락되었습니다.', 'pgall-for-woocommerce' ), '1001', 'PAFW-1001' );
				}

				$ids      = explode( '_', $_REQUEST['LGD_OID'] );
				$order_id = (int) $ids[0];
				$order    = wc_get_order( $order_id );

				if ( ! $order ) {
					throw new PAFW_Exception( __( '유효하지않은 주문입니다.', 'pgall-for-woocommerce' ), '1002', 'PAFW-1002' );
				}

				$this->validate_order_status( $order );
				$CST_PLATFORM = 'production' == $this->operation_mode ? 'service' : 'test';
				$CST_MID      = $this->merchant_id;
				$LGD_MID      = ( 'sandbox' == $this->operation_mode ? 't' : '' ) . $CST_MID;
				$LGD_PAYKEY   = $_POST["LGD_PAYKEY"];

				require_once( PAFW()->plugin_path() . '/lib/lguplus/lgdacom/XPayClient.php' );
				$xpay = new XPayClient( $this->config_path, $CST_PLATFORM );

				$xpay->config[ $LGD_MID ] = $this->merchant_key;
				$xpay->Init_TX( $LGD_MID );
				$xpay->Set( "LGD_TXNAME", "PaymentByKey" );
				$xpay->Set( "LGD_PAYKEY", $LGD_PAYKEY );

				if ( $xpay->TX() ) {
					if ( "0000" == $xpay->Response_Code() ) {
						pafw_update_meta_data( $order, "_pafw_payment_method", $xpay->Response( 'LGD_PAYTYPE', 0 ) );
						pafw_update_meta_data( $order, "_pafw_txnid", $xpay->Response( 'LGD_OID', 0 ) );
						pafw_update_meta_data( $order, "_pafw_payed_date", $xpay->Response( 'LGD_TIMESTAMP', 0 ) );
						pafw_update_meta_data( $order, "_pafw_total_price", $xpay->Response( 'LGD_AMOUNT', 0 ) );
						$this->process_payment_success( $order, $xpay );

						$this->payment_complete( $order, $xpay->Response( 'LGD_TID', 0 ) );
					} else {
						throw new PAFW_Exception( sprintf( __( '결제 승인 요청 과정에서 오류가 발생했습니다. 관리자에게 문의해주세요. 오류코드(%s), 오류메시지(%s)', 'pgall-for-woocommerce' ), esc_attr( $xpay->Response_Code() ), esc_attr( mb_convert_encoding( $xpay->Response_Msg(), "UTF-8", "CP949" ) ) ), '3004', $xpay->Response_Code() );
					}
				} else {
					throw new PAFW_Exception( sprintf( __( '결제 승인 요청 과정에서 오류가 발생했습니다. 관리자에게 문의해주세요. 오류코드(%s), 오류메시지(%s)', 'pgall-for-woocommerce' ), esc_attr( $xpay->Response_Code() ), esc_attr( mb_convert_encoding( $xpay->Response_Msg(), "UTF-8", "CP949" ) ) ), '3005', $xpay->Response_Code() );
				}
			} catch ( Exception $e ) {
				$error_code = '';
				if ( $e instanceof PAFW_Exception ) {
					$error_code = $e->getErrorCode();
				}

				$message = sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() );
				$this->add_log( "[오류] " . $message . "\n" . print_r( $_REQUEST, true ) );

				wc_add_notice( $message, 'error' );
				if ( $order ) {
					$order->add_order_note( $message );
					if ( empty( pafw_get_object_property( $order, 'paid_date' ) ) ) {
						$order->update_status( 'failed', __( 'LG유플러스 결제내역을 확인하신 후, 고객에게 연락을 해주시기 바랍니다.', 'pgall-for-woocommerce' ) );
					}
				}

				do_action( 'pafw_payment_fail', $order, ! empty( $error_code ) ? $error_code : $e->getCode(), $e->getMessage() );

				wp_safe_redirect( wc_get_page_permalink( 'checkout' ) );
				die();
			}
		}
		function process_payment_response() {

			if ( ! empty( $_REQUEST['type'] ) ) {
				$this->add_log( "Process Payment Response : " . $_REQUEST['type'] );

				$this->add_log( print_r( $_REQUEST, true ) );

				$oid = empty( $_REQUEST['LGD_OID'] ) ? '' : $_REQUEST['LGD_OID'];

				//전달값에서 주문번호 추출
				if ( $oid ) {
					$ids = explode( '_', $oid );

					//주문번호 분리하여 주문 로딩
					if ( ! empty( $ids ) ) {
						$order_id = intval( $ids[0] );
						$order    = wc_get_order( $order_id );
					}
				}

				switch ( $_REQUEST['type'] ) {
					case "return":
						$payment_url = $this->get_api_url( 'payment' );
						ob_start();
						include( 'templates/returnurl.php' );
						echo ob_get_clean();
						die();
					case "payment":
						$this->do_payment();
						$this->redirect_page( $order_id );
						break;
					case "vbank_noti":  //가상계좌 입금통보 수신
						$this->process_vbank_noti();
						$this->redirect_page( $order_id );
						break;
					case "delivery":    //에스크로 실시간 계좌이체 배송정보 등록 처리
						if ( get_class( $this ) == 'WC_Gateway_Lguplus_Escrowbank' ) {
							$this->lguplus_escrow_delivery_add( $_POST );
						}
						break;
					case "delivery_confirm":    //에스크로 실시간 계좌이체 구매확인/거절 처리
						if ( get_class( $this ) == 'WC_Gateway_Lguplus_Escrowbank' ) {
							$this->lguplus_escrow_delivery_confirm( $_POST );
						}
						break;
					default :
						if ( empty( $return_type[0] ) ) {
							$this->add_log( 'Request Type 값없음 종료.' . print_r( $_REQUEST, true ) );
							wp_die( __( '결제 요청 실패 : 관리자에게 문의하세요!', 'pgall-for-woocommerce' ) );
						} else {
							do_action( 'lguplus_ajax_response', $return_type[0] );
						}
						break;
				}
			} else {
				$this->add_log( '[처리종료] 올바르지 않은 요청입니다.' );
				wp_die( __( '결제 요청 실패 : 관리자에게 문의하세요!', 'pgall-for-woocommerce' ) );
			}

			$this->add_log( 'Response() 처리 종료' . print_r( $_REQUEST, true ) );
		}
		public static function get_lguplus_log_path() {
			$upload_dir = wp_upload_dir();

			$path = $upload_dir['basedir'] . '/lguplus_log/';
			wp_mkdir_p( $path );

			if ( ! file_exists( $path . '/.htaccess' ) ) {
				$pfile = fopen( $path . '/.htaccess', "w" );
				$txt   = "<Files *.log>\nRequire all denied\n</Files>\n";
				fwrite( $pfile, $txt );
				$txt = "<Files *.log>\ndeny from all\n</Files>\n";
				fwrite( $pfile, $txt );
				fclose( $pfile );
			}

			return $path;
		}

		public function process_payment_success( $order, $xpay ) {
		}
		public function add_meta_box_action_button( $order ) {
			$tid = $order->get_transaction_id();

			$mid = ( 'sandbox' == $this->operation_mode ? 't' : '' ) . $this->merchant_id;
			$key = $this->merchant_key;

			$authdata = md5( $mid . $tid . $key );

			if ( 'sandbox' == $this->operation_mode ) {
				wp_enqueue_script( 'lguplus', '//pgweb.uplus.co.kr:7085/WEB_SERVER/js/receipt_link.js' );
			} else {
				wp_enqueue_script( 'lguplus', '//pgweb.uplus.co.kr/WEB_SERVER/js/receipt_link.js' );
			}

			?>
            <a class="button pafw_action_button tips" style="text-align: center;" href="javascript:showReceiptByTID('<?php echo $mid; ?>', '<?php echo $tid; ?>', '<?php echo $authdata; ?>')">영수증 출력</a>
			<?php
		}
		function is_test_key() {
			return in_array( $this->merchant_id, $this->key_for_test );
		}

		public function get_merchant_id() {
			return pafw_get( $this->settings, 'merchant_id' );
		}
	}
}