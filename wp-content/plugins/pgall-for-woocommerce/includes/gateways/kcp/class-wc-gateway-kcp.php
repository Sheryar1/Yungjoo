<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {
// PAFW()->plugin_path() . '/includes/gateways/mshop-kcp/
	include_once( 'class-encrypt.php' );

	class WC_Gateway_KCP extends PAFW_Payment_Gateway {
		const REQ_TX_PAY = 'pay';
		const REQ_TX_CANCEL = 'mod';
		const TX_VACC_DEPOSIT = 'TX00';
		const TX_ESCROW_CONFIRM = 'TX02';
		const TX_ESCROW_DELIVERY = 'TX03';
		const TX_ESCROW_WITHHOLD_SETTLEMENT = 'TX04';
		const TX_ESCROW_CANCEL_IMMEDIATELY = 'TX05';
		const TX_ESCROW_CANCEL = 'TX06';

		// KCP 모듈 타입
		const MODULE_TYPE = '01';

		// 테스트 환경용 파라미터
		static $sandbox = array (
			'site_cd'        => 'T0000',
			'site_key'       => '3grptw1.zW0GSo4PQdaGvsF__',
			'gw_url'         => 'testpaygw.kcp.co.kr',
			'log_level'      => '3',
			'js_url'         => 'https://testpay.kcp.co.kr/plugin/payplus_web.jsp',
			'wsdl'           => 'KCPPaymentService.wsdl',
			'bills_url'      => 'https://testadmin8.kcp.co.kr/assist/bill.BillActionNew.do',
			'cash_bills_url' => 'https://testadmin8.kcp.co.kr/Modules/Service/Cash/Cash_Bill_Common_View.jsp'
		);

		static $sandbox_escrow = array (
			'site_cd'        => 'T0007',
			'site_key'       => '4Ho4YsuOZlLXUZUdOxM1Q7X__',
			'gw_url'         => 'testpaygw.kcp.co.kr',
			'log_level'      => '3',
			'js_url'         => 'https://testpay.kcp.co.kr/plugin/payplus_web.jsp',
			'wsdl'           => 'KCPPaymentService.wsdl',
			'bills_url'      => 'https://testadmin8.kcp.co.kr/assist/bill.BillActionNew.do',
			'cash_bills_url' => 'https://testadmin8.kcp.co.kr/Modules/Service/Cash/Cash_Bill_Common_View.jsp'
		);

		// 운영 환경용 파라미터
		static $production = array (
			'gw_url'         => 'paygw.kcp.co.kr',
			'gw_port'        => '8090',
			'log_level'      => '3',
			'js_url'         => 'https://pay.kcp.co.kr/plugin/payplus_web.jsp',
			'wsdl'           => 'real_KCPPaymentService.wsdl',
			'bills_url'      => 'https://admin8.kcp.co.kr/assist/bill.BillActionNew.do',
			'cash_bills_url' => 'https://admin.kcp.co.kr/Modules/Service/Cash/Cash_Bill_Common_View.jsp'
		);

		static $mobile_pay_method_desc = array (
			'card' => '신용카드',
			'acnt' => '계좌이체',
			'vcnt' => '가상계좌',
			'mobx' => '휴대폰',
			'ocb'  => 'OK캐쉬백',
			'tpnt' => '복지포인트',
			'scbl' => '도서상품권',
			'sccl' => '문화상품권',
			'schm' => '해피머니',
		);

		static $pc_pay_method_desc = array (
			'100000000000' => '신용카드',
			'010000000000' => '계좌이체',
			'001000000000' => '가상계좌',
			'000100000000' => '포인트',
			'000010000000' => '휴대폰',
			'000000001000' => '상품권',
			'000000000010' => 'ARS',
		);

		static $card_company = array (
			'CCLG' => '신한',
			'CCDI' => '현대',
			'CCLO' => '롯데',
			'CCKE' => '외환',
			'CCSS' => '삼성',
			'CCKM' => '국민',
			'CCBC' => '비씨',
			'CCNH' => '농협',
			'CCHN' => '하나 SK',
			'CCCT' => '씨티',
			'CCPH' => '우리',
			'CCKJ' => '광주',
			'CCSU' => '수협',
			'CCJB' => '전북',
			'CCCJ' => '제주',
			'CCKD' => 'KDB 산은',
			'CCSB' => '저축',
			'CCCU' => '신협',
			'CCPB' => '우체국',
			'CCSM' => 'MG 새마을',
			'CCXX' => '해외',
			'CCUF' => '은련',
			'BC81' => '하나비씨'
		);

		static $noint_quota_month = array (
			"02" => 2,
			"03" => 3,
			"04" => 4,
			"05" => 5,
			"06" => 6,
			"07" => 7,
			"08" => 8,
			"09" => 9,
			"10" => 10,
			"11" => 11,
			"12" => 12
		);

		static $vbank_list = array (
			'03' => '기업은행',
			'04' => '국민은행',
			'05' => '외환은행',
			'07' => '수협',
			'11' => '농협',
			'20' => '우리은행',
			'23' => 'SC은행',
			'26' => '신한은행',
			'32' => '부산은행',
			'34' => '광주은행',
			'71' => '우체국',
			'81' => '하나은행'
		);

		static $bills_cmd = array (
			'100000000000' => 'card_bill',
			'010000000000' => 'acnt_bill',
			'001000000000' => 'vcnt_bill',
			'000010000000' => 'mcash_bill'
		);

		protected $key_for_test = array (
			'T0000',
			'T0007'
		);

		public function __construct() {

			$this->master_id = 'kcp';

			$this->pg_title     = __( 'NHN KCP', 'pgall-for-woocommerce' );
			$this->method_title = __( 'NHN KCP', 'pgall-for-woocommerce' );

			parent::__construct();
		}

		public static function enqueue_frontend_script() {
			$options = get_option( 'pafw_mshop_kcp' );

			if ( 'sandbox' === pafw_get( $options, 'operation_mode', 'sandbox' ) ) {
				wp_enqueue_script( 'payplus-web', self::$sandbox['js_url'] );
			} else {
				wp_enqueue_script( 'payplus-web', self::$production['js_url'] );
			}

			?>
            <style>
                #NAX_BLOCK {
                    z-index: 99999 !important;
                }
            </style>
			<?php
		}

		public function get_transaction_url( $order ) {
			$return_url = '';

			$bills_url = $this->kcpfw_option( 'bills_url' );
			$bills_cmd = $this->kcpfw_option( 'bills_cmd' );
			$tno       = $order->get_transaction_id();
			$amount    = pafw_get_meta( $order, '_pafw_total_price', true );

			if ( ! empty( $tno ) ) {
				$return_url = sprintf( "%s?cmd=%s&tno=%s&order_no=%s&trade_mony=%s", $bills_url, $bills_cmd, $tno, pafw_get_object_property( $order, 'id' ), $amount );
			}

			return apply_filters( 'woocommerce_get_transaction_url', $return_url, $order, $this );
		}

		function is_mac() {
			return strpos( $_SERVER['HTTP_USER_AGENT'], "Mac" ) !== false;
		}
		function log_path() {
			return PAFW()->plugin_path() . '/logs';
		}
		function home_dir() {
			if ( 8 == PHP_INT_SIZE ) {
				return PAFW()->plugin_path() . '/lib/kcp/64';
			} else {
				return PAFW()->plugin_path() . '/lib/kcp/32';
			}
		}

		function get_kcp_noint_quota() {
			$rules = json_decode( $this->settings['kcp_noint_quota'] );

			if ( empty( $rules ) ) {
				$rules = array ();
			}

			return array_values( $rules );
		}

		function set_kcp_noint_quota() {
			$this->settings['kcp_noint_quota'] = json_encode( $_REQUEST['kcp_noint_quota'] );
		}

		function kcpfw_option( $key ) {
			if ( 'sandbox' === $this->settings['operation_mode'] && ! empty( self::$sandbox[ $key ] ) ) {
				if ( $this->is_escrow ) {
					return self::$sandbox_escrow[ $key ];
				} else {
					return self::$sandbox[ $key ];
				}
			} else {
				return ! empty( self::$production[ $key ] ) ? self::$production[ $key ] : $this->settings[ $key ];
			}
		}
		public function request_payment() {
			$this->add_log( 'Request Payment' );

			try {

				if ( isset( $_REQUEST['data'] ) ) {
					$payment_info = array ();
					parse_str( $_REQUEST['data'], $payment_info );
					$_REQUEST = array_merge( $_REQUEST, $payment_info );
				}

				require_once $this->home_dir() . '/pp_cli_hub_lib.php';
				$this->check_requirement();

				$order = $this->get_order();

				$tran_cd   = $_REQUEST["tran_cd"]; // 처리 종류
				$cust_ip   = getenv( "REMOTE_ADDR" ); // 요청 IP
				$ordr_idxx = $_REQUEST["ordr_idxx"]; // 쇼핑몰 주문번호

				$c_PayPlus = new C_PP_CLI;
				$c_PayPlus->mf_clear();
				$order_total = apply_filters( 'kcp-for-woocommerce-order-total', $order->get_total(), $this, $order );
				$c_PayPlus->mf_set_ordr_data( "ordr_mony", $order_total );
				$c_PayPlus->mf_set_encx_data( $_REQUEST["enc_data"], $_REQUEST["enc_info"] );

				if ( $tran_cd != "" ) {
					$c_PayPlus->mf_do_tx(
						"",
						$this->home_dir(),
						$this->kcpfw_option( 'site_cd' ),
						$this->kcpfw_option( 'site_key' ),
						$tran_cd,
						"",
						$this->kcpfw_option( 'gw_url' ),
						$this->kcpfw_option( 'gw_port' ),
						"payplus_cli_slib",
						$ordr_idxx,
						$cust_ip,
						$this->kcpfw_option( 'log_level' ),
						0,
						$this->home_dir() . '/bin/pub.key',
						$this->log_path() ); // 응답 전문 처리

					$res_cd  = $c_PayPlus->m_res_cd;  // 결과 코드
					$res_msg = iconv( 'euc-kr', 'UTF-8', $c_PayPlus->m_res_msg ); // 결과 메시지
				} else {
					throw new PAFW_Exception( __( '연동 오류|Payplus Plugin이 설치되지 않았거나 tran_cd값이 설정되지 않았습니다.', 'pgall-for-woocommerce' ), '3001', '9562' );
				}

				$this->add_log( 'Response Code : ' . $res_cd . ', Response Message : ' . $res_msg );

				if ( $res_cd == "0000" ) {
					pafw_update_meta_data( $order, "_pafw_payment_method", $c_PayPlus->mf_get_res_data( "pay_method" ) );
					pafw_update_meta_data( $order, "_pafw_txnid", $c_PayPlus->mf_get_res_data( "order_no" ) );
					pafw_update_meta_data( $order, "_pafw_payed_date", $c_PayPlus->mf_get_res_data( "app_time" ) );
					pafw_update_meta_data( $order, "_pafw_total_price", $c_PayPlus->mf_get_res_data( "amount" ) );

					$this->process_payment_result( $order, $c_PayPlus );

					$this->payment_complete( $order, $c_PayPlus->mf_get_res_data( "tno" ) );

					if ( is_ajax() ) {
						wp_send_json_success( $order->get_checkout_order_received_url() );
					} else {
						wp_safe_redirect( $order->get_checkout_order_received_url() );
						die();
					}
				} else {
					throw new PAFW_Exception( $res_msg, '3002', $res_cd );
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
						$order->update_status( 'failed', __( 'KCP 결제내역을 확인하신 후, 고객에게 연락을 해주시기 바랍니다.', 'pgall-for-woocommerce' ) );
					}
				}

				do_action( 'pafw_payment_fail', $order, ! empty( $error_code ) ? $error_code : $e->getCode(), $e->getMessage() );

				if ( wp_is_mobile() ) {
					wc_add_notice( $message, 'error' );
					wp_safe_redirect( $order->get_checkout_payment_url() );
					die();
				} else {
					wp_send_json_error( $message );
				}
			}

		}

		function process_order_pay() {
			if ( wp_is_mobile() ) {
				wp_send_json_success( $this->request_approval( $_REQUEST['order_id'], $_REQUEST['order_key'] ) );
			} else {
				wp_send_json_success( $this->get_payment_form( $_REQUEST['order_id'], $_REQUEST['order_key'] ) );
			}
		}

		function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			do_action( 'pafw_process_payment', $order );

			if ( wp_is_mobile() ) {
				return $this->request_approval( $order_id, pafw_get_object_property( $order, 'order_key' ) );
			} else {
				return $this->get_payment_form( $order_id, pafw_get_object_property( $order, 'order_key' ) );
			}
		}
		public function get_payment_form( $order_id, $order_key ) {
			try {
				$this->check_requirement();

				$this->permission_process();

				$order = $this->get_order( $order_id, $order_key );

				pafw_set_browser_information( $order );
				$this->has_enough_stock( $order );
				$order->set_payment_method( $this );
				if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
					$order->save();
				}

				ob_start();
				include( 'templates/payment-form' . ( wp_is_mobile() ? '-mobile' : '' ) . '.php' );
				$form_tag = ob_get_clean();

				return array (
					'result'       => 'success',
					'payment_form' => '<div data-id="mshop-payment-form" style="display:none">' . $form_tag . '</div>'
				);
			} catch ( Exception $e ) {
				wc_add_notice( $e->getMessage(), 'error' );
			}
		}

		public function check_pay_method( $method ) {
			if ( 'card' === $method ) {
				return array (
					'pay_method' => 'CARD',
					'van_code'   => ''
				);
			} else if ( 'acnt' === $method ) {
				return array (
					'pay_method' => 'BANK',
					'van_code'   => ''
				);
			} else if ( 'vcnt' === $method ) {
				return array (
					'pay_method' => 'VCNT',
					'van_code'   => ''
				);
			} else if ( 'mobx' === $method ) {
				return array (
					'pay_method' => 'MOBX',
					'van_code'   => ''
				);
			} else if ( 'ocb' === $method ) {
				return array (
					'pay_method' => 'TPNT',
					'van_code'   => 'SCSK'
				);
			} else if ( 'tpnt' === $method ) {
				return array (
					'pay_method' => 'TPNT',
					'van_code'   => 'SCWB'
				);
			} else if ( 'scbl' === $method ) {
				return array (
					'pay_method' => 'GIFT',
					'van_code'   => 'SCBL'
				);
			} else if ( 'sccl' === $method ) {
				return array (
					'pay_method' => 'GIFT',
					'van_code'   => 'SCCL'
				);
			} else if ( 'schm' === $method ) {
				return array (
					'pay_method' => 'GIFT',
					'van_code'   => 'SCHM'
				);
			}
		}
		public function request_approval( $order_id, $order_key ) {
			$this->add_log( 'Request Approval' );

			try {
				$this->permission_process();

				require_once $this->home_dir() . '/KCPComLibrary.php';

				$order = wc_get_order( $order_id );

				$paymentMethodInfo = $this->check_pay_method( $this->settings['mobile_paymethod'] );

				// 쇼핑몰 페이지에 맞는 문자셋을 지정해 주세요.
				$charSetType = "utf-8";             // UTF-8인 경우 "utf-8"로 설정

				$siteCode      = $this->kcpfw_option( 'site_cd' );
				$orderID       = $order_id;
				$paymentMethod = $paymentMethodInfo['pay_method'];
				$escrow        = $this->is_escrow ? true : false;
				$productName   = $this->make_product_info( $order );

				$paymentAmount = apply_filters( 'kcp-for-woocommerce-order-total', $order->get_total(), $this, $order );
				$returnUrl     = site_url() . '/KCPPaymentResult';

				// Access Credential 설정
				$accessLicense = "";
				$signature     = "";
				$timestamp     = "";

				// Base Request Type 설정
				$detailLevel = "0";
				$requestApp  = "WEB";
				$requestID   = $orderID;
				$userAgent   = $_SERVER['HTTP_USER_AGENT'];
				$version     = "0.1";

				try {
					$payService = new PayService( $this->home_dir() . '/' . $this->kcpfw_option( 'wsdl' ) );

					$payService->setCharSet( $charSetType );

					$payService->setAccessCredentialType( $accessLicense, $signature, $timestamp );
					$payService->setBaseRequestType( $detailLevel, $requestApp, $requestID, $userAgent, $version );
					$payService->setApproveReq( $escrow, $orderID, $paymentAmount, $paymentMethod, $productName, $returnUrl, $siteCode );

					$approveRes = $payService->approve();

					$this->add_log( 'Response Code : ' . $payService->resCD . ', Response Message : ' . $payService->resMsg );

					return array (
						'result'         => 'success',
						'payment_form'   => '<div data-id="mshop-payment-form" style="display:none">' . $this->get_payment_form_mobile( $order, $order_id, $_REQUEST['order_key'], $approveRes->payUrl, $approveRes->approvalKey ) . '</div>'
					);
				} catch ( SoapFault $ex ) {
					throw new PAFW_Exception( __( '연동 오류 (PHP SOAP 모듈 설치 필요)', 'pgall-for-woocommerce' ), '4003', '95XX' );
				}
			} catch ( Exception $e ) {
				$error_code = '';
				if ( $e instanceof PAFW_Exception ) {
					$error_code = $e->getErrorCode();
				}

				$message = sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() );
				$this->add_log( "[오류]\n" . $message . "\n" . print_r( $_REQUEST, true ) );

				wc_add_notice( $message, 'error' );
				if ( $order ) {
					$order->add_order_note( $message );
					if ( empty( pafw_get_object_property( $order, 'paid_date' ) ) ) {
						$order->update_status( 'failed', __( 'KCP 결제내역을 확인하신 후, 고객에게 연락을 해주시기 바랍니다.', 'pgall-for-woocommerce' ) );
					}
				}

				do_action( 'pafw_payment_fail', $order, ! empty( $error_code ) ? $error_code : $e->getCode(), $e->getMessage() );

				wc_add_notice( $message, 'error' );
			}
		}
		public function get_payment_form_mobile( $order, $order_id, $order_key, $pay_url, $approval_key ) {

			pafw_set_browser_information( $order );

			$paymentMethodInfo = $this->check_pay_method( $this->settings['mobile_paymethod'] );
			$order->set_payment_method( $this );

			ob_start();

			include( 'templates/payment-form-mobile.php' );

			return ob_get_clean();
		}
		function cancel_request( $order, $msg, $code = "1" ) {
			$transaction_id = $this->get_transaction_id( $order );

			require_once $this->home_dir() . '/pp_cli_hub_lib.php';

			$c_PayPlus = new C_PP_CLI;

			$c_PayPlus->mf_clear();

			$tran_cd = "00200000";

			$c_PayPlus->mf_set_modx_data( "tno", $transaction_id );  // KCP 원거래 거래번호
			$c_PayPlus->mf_set_modx_data( "mod_type", "STSC" );  // 원거래 변경 요청 종류

			if ( is_admin() ) {
				$c_PayPlus->mf_set_modx_data( "mod_ip", $_SERVER['SERVER_ADDR'] );  // 변경 요청자 IP
				$c_PayPlus->mf_set_modx_data( "mod_desc", "관리자 환불 처리" );
			} else {
				$c_PayPlus->mf_set_modx_data( "mod_ip", getenv( "REMOTE_ADDR" ) );  // 변경 요청자 IP
				$c_PayPlus->mf_set_modx_data( "mod_desc", "사용자 주문 취소" );
			}

			$c_PayPlus->mf_do_tx(
				"",
				$this->home_dir(),
				$this->kcpfw_option( 'site_cd' ),
				$this->kcpfw_option( 'site_key' ),
				$tran_cd,
				"",
				$this->kcpfw_option( 'gw_url' ),
				$this->kcpfw_option( 'gw_port' ),
				"payplus_cli_slib",
				pafw_get_object_property( $order, 'id' ),
				'',
				$this->kcpfw_option( 'log_level' ),
				0,
				0,
				$this->log_path() ); // 응답 전문 처리

			$res_cd      = $c_PayPlus->m_res_cd;
			$res_msg     = $c_PayPlus->m_res_msg;
			$cancel_date = $c_PayPlus->mf_get_res_data( "canc_time" );
			if ( empty( $cancel_date ) ) {
				$cancel_date = $c_PayPlus->mf_get_res_data( 'mod_time' );
			}

			if ( $res_cd == '0000' ) {
				do_action( 'pafw_payment_action', 'cancelled', $order->get_total(), $order, $this );

				return "success";
			} else {
				throw new Exception( '주문취소중 오류가 발생했습니다. [' . $res_cd . '] ' . iconv( 'euc-kr', 'UTF-8', $res_msg ) );
			}
		}

		function send_common_return_response() {
			header( 'HTTP/1.1 200 OK' );
			header( "Content-Type: text; charset=euc-kr" );
			header( "Cache-Control: no-cache" );
			header( "Pragma: no-cache" );

			echo '<html><body><form><input type="hidden" name="result" value="0000"></form></body></html>';
			die();
		}

		function process_payment_response() {
			$this->add_log( "Process Payment Response : " . $_REQUEST['type'] . ", Response Code : " . $_REQUEST['res_cd'] );

			if ( ! empty( $_REQUEST ) ) {

				if ( ! empty( $_REQUEST['type'] ) ) {
					if ( strpos( $_REQUEST['type'], '?' ) !== false ) {
						$return_type          = explode( '?', $_REQUEST['type'] );
						$_REQUEST['type']     = $return_type[0];
						$tmp_status           = explode( '=', $return_type[1] );
						$_REQUEST['P_STATUS'] = $tmp_status[1];
					} else {
						$return_type = explode( ',', $_REQUEST['type'] );
					}

					switch ( $return_type[0] ) {
						case 'payment' :
							if ( $_REQUEST['res_cd'] === '3001' ) {
								do_action( 'pafw_payment_cancel' );

								wp_safe_redirect( $_REQUEST['param_opt_3'] );
								die();
							} else if ( $_REQUEST['res_cd'] === '0000' ) {

								$_REQUEST['order_id']  = $_REQUEST['param_opt_1'];
								$_REQUEST['order_key'] = $_REQUEST['param_opt_2'];

								$this->request_payment( $_REQUEST );
							}
							break;
						case "common_return" :
							$this->process_common_return();
							break;
						default :
							if ( empty( $return_type[0] ) ) {
								$this->add_log( "Request Type 없음 종료.\n" . print_r( $_REQUEST, true ) );
								wp_die( __( '결제 요청 실패 : 관리자에게 문의하세요!', 'pgall-for-woocommerce' ) );
							} else {
								do_action( 'inicis_ajax_response', $return_type[0] );
							}
							break;
					}
				} else {
					$this->add_log( "Request Type 없음 종료.\n" . print_r( $_REQUEST, true ) );
					wp_die( __( '결제 요청 실패 : 관리자에게 문의하세요!', 'pgall-for-woocommerce' ) );
				}
			} else {
				$this->add_log( "Request 없음 종료.\n" . print_r( $_REQUEST, true ) );
				wp_die( __( '결제 요청 실패 : 관리자에게 문의하세요!', 'pgall-for-woocommerce' ) );
			}
		}

		function process_common_return() {
		}

		function cancel_payment_request_by_user() {
			do_action( 'pafw_payment_cancel' );
			wp_send_json_success();
		}
		function is_test_key() {
			return in_array( $this->kcpfw_option( 'site_cd' ), $this->key_for_test );
		}

		public function get_receipt_popup_params() {
			return array (
				'name'     => 'showreceipt',
				'features' => 'width=470,height=815, scrollbars=no,resizable=no'
			);
		}
		private function permission_process() {
			chmod( PAFW()->plugin_path() . '/lib/kcp/32/bin/pp_cli', '0755' );
			chmod( PAFW()->plugin_path() . '/lib/kcp/64/bin/pp_cli', '0755' );
		}

		public function get_merchant_id() {
			return pafw_get( $this->settings, 'site_cd' );
		}
	}

}