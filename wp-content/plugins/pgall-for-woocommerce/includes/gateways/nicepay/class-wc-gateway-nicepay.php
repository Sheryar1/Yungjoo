<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {
	class WC_Gateway_Nicepay extends PAFW_Payment_Gateway {

		public static $log;

		protected $success_code;

		protected $key_for_test = array (
			'nicepay00m',
			'nictest04m'
		);
		public function __construct() {
			$this->master_id = 'nicepay';

			$this->view_transaction_url = 'https://npg.nicepay.co.kr/issue/IssueLoaderMail.do?TID=%s&type=0';

			$this->pg_title     = __( '나이스페이', 'pgall-for-woocommerce' );
			$this->method_title = __( '나이스페이', 'pgall-for-woocommerce' );

			parent::__construct();
		}

		public static function enqueue_frontend_script() {
			?>
            <script type=text/javascript src="//web.nicepay.co.kr/flex/js/nicepay_tr_utf.js"></script>
			<?php

			if ( ! is_checkout_pay_page() ) {
				wp_register_style( 'nfw-style', PAFW()->plugin_url() . '/assets/gateways/nicepay/css/style.css' );
				wp_enqueue_style( 'nfw-style' );
			}
		}
		function check_requirement() {

			parent::check_requirement();

			if ( ! file_exists( PAFW()->plugin_path() . "/lib/nicepay/nicepay/web/NicePayWEB.php" ) ) {
				throw new Exception( __( '[ERR-PAFW-0003] NicePayWEB.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ) );
			}
			if ( ! file_exists( PAFW()->plugin_path() . "/lib/nicepay/nicepay/core/Constants.php" ) ) {
				throw new Exception( __( '[ERR-PAFW-0003] Constants.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ) );
			}

			if ( ! file_exists( PAFW()->plugin_path() . "/lib/nicepay/nicepay/web/NicePayHttpServletRequestWrapper.php" ) ) {
				throw new Exception( __( '[ERR-PAFW-0003] NicePayHttpServletRequestWrapper.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ) );
			}
		}
		function get_order_from_txnid( $txnid ) {
			$order = wc_get_order( $this->get_order_id_from_txnid( $txnid ) );

			if ( ! $order ) {
				throw new PAFW_Exception( __( '유효하지않은 주문입니다.', 'pgall-for-woocommerce' ), '1001', 'PAFW-1001' );
			}
			$this->validate_order_status( $order );
			if ( ! $this->validate_txnid( $order, $txnid ) ) {
				throw new PAFW_Exception( sprintf( __( '유효하지 않은 주문번호(%s) 입니다.', 'pgall-for-woocommerce' ), $txnid ), '1002', 'PAFW-1002' );
			}

			return $order;
		}
		function get_payment_description( $paymethod ) {
			switch ( $paymethod ) {
				case "card":
					return __( '신용카드', 'pgall-for-woocommerce' );
					break;
				case "bank":
					return __( '실시간계좌이체', 'pgall-for-woocommerce' );
					break;
				case "vbank":
					return __( '가상계좌', 'pgall-for-woocommerce' );
					break;
				case "escrowbank":
					return __( '휴대폰', 'pgall-for-woocommerce' );
					break;
				default:
					return $paymethod;
					break;
			}
		}
		function cancel_request( $order, $msg, $code = "1" ) {
			$transaction_id = $this->get_transaction_id( $order );

			//리턴 데이터
			$result_data = array ();

			require_once PAFW()->plugin_path() . '/lib/nicepay/nicepay/web/NicePayWEB.php';
			require_once PAFW()->plugin_path() . '/lib/nicepay/nicepay/core/Constants.php';
			require_once PAFW()->plugin_path() . '/lib/nicepay/nicepay/web/NicePayHttpServletRequestWrapper.php';

			//취소에 필요한 정보 설정
			$_REQUEST['MID']               = $this->settings['merchant_id'];
			$_REQUEST['TID']               = $transaction_id;
			$_REQUEST['CancelAmt']         = $order->get_total();
			$_REQUEST['CancelMsg']         = $msg;
			$_REQUEST['CancelPwd']         = $this->settings['cancel_pw'];
			$_REQUEST['PartialCancelCode'] = '0';  //0:전체취소, 1:부분취소

			$this->add_log( __( '주문취소 요청처리(' . $msg . ') = ' . print_r( $_REQUEST, true ), 'pgall-for-woocommerce' ), 'nicepay' );

			$httpRequestWrapper = new NicePayHttpServletRequestWrapper( $_REQUEST );
			$_REQUEST           = $httpRequestWrapper->getHttpRequestMap();
			$nicepayWEB         = new NicePayWEB();

			$nicepayWEB->setParam( "NICEPAY_LOG_HOME", $this->get_nicepay_log_path() );             // 로그 디렉토리 설정
			$nicepayWEB->setParam( "APP_LOG", "1" );                           // 이벤트로그 모드 설정(0: DISABLE, 1: ENABLE)
			$nicepayWEB->setParam( "EVENT_LOG", "1" );                         // 어플리케이션로그 모드 설정(0: DISABLE, 1: ENABLE)
			$nicepayWEB->setParam( "EncFlag", "S" );                           // 암호화플래그 설정(N: 평문, S:암호화)
			$nicepayWEB->setParam( "SERVICE_MODE", "CL0" );                   // 서비스모드 설정(결제 서비스 : PY0 , 취소 서비스 : CL0)
			$nicepayWEB->setParam( "CHARSET", "UTF8" );                       // 인코딩

			//취소 요청 처리 (ob_start 처리를 하지 않으면 doService 할때 출력되는 문자열 제거할수 없어 추가됨)
			ob_start();
			$responseDTO = $nicepayWEB->doService( $_REQUEST );
			ob_end_clean();

			//취소 처리 결과 확인
			$resultCode = trim( $responseDTO->getParameter( "ResultCode" ) );        // 결과코드 (취소성공: 2001, 취소진행중: 2002)
			$resultMsg  = trim( $responseDTO->getParameterUTF( "ResultMsg" ) );      // 결과메시지
			$cancelAmt  = trim( $responseDTO->getParameter( "CancelAmt" ) );         // 취소금액
			$cancelDate = trim( $responseDTO->getParameter( "CancelDate" ) );        // 취소일
			$cancelTime = trim( $responseDTO->getParameter( "CancelTime" ) );        // 취소시간
			$cancelNum  = trim( $responseDTO->getParameter( "CancelNum" ) );         // 취소번호
			$payMethod  = trim( $responseDTO->getParameter( "PayMethod" ) );         // 취소 결제수단
			$mid        = trim( $responseDTO->getParameter( "MID" ) );               // 상점 ID
			$tid        = trim( $responseDTO->getParameter( "TID" ) );               // 거래아이디 TID

			if ( $resultCode == "2001" ) {
				do_action( 'pafw_payment_action', 'cancelled', $order->get_total(), $order, $this );

				return "success";
			} else {
				throw new Exception( $resultMsg );
			}
		}
		function process_response_payment( $posted ) {
			$this->check_requirement();

			try {
				require_once PAFW()->plugin_path() . '/lib/nicepay/nicepay/web/NicePayWEB.php';
				require_once PAFW()->plugin_path() . '/lib/nicepay/nicepay/core/Constants.php';
				require_once PAFW()->plugin_path() . '/lib/nicepay/nicepay/web/NicePayHttpServletRequestWrapper.php';

				$order = $this->get_order_from_txnid( $_REQUEST['Moid'] );
				$nicepayWEB         = new NicePayWEB();
				$httpRequestWrapper = new NicePayHttpServletRequestWrapper( $_REQUEST );
				$_REQUEST           = $httpRequestWrapper->getHttpRequestMap();
				$payMethod          = $_REQUEST['PayMethod'];
				$merchantKey        = $this->settings['merchant_key'];

				$nicepayWEB->setParam( "NICEPAY_LOG_HOME", $this->get_nicepay_log_path() );             // 로그 디렉토리 설정
				$nicepayWEB->setParam( "APP_LOG", "1" );                           // 어플리케이션로그 모드 설정(0: DISABLE, 1: ENABLE)
				$nicepayWEB->setParam( "EncFlag", "S" );                           // 암호화플래그 설정(N: 평문, S:암호화)
				$nicepayWEB->setParam( "SERVICE_MODE", "PY0" );                   // 서비스모드 설정(결제 서비스 : PY0 , 취소 서비스 : CL0)
				$nicepayWEB->setParam( "Currency", "KRW" );                       // 통화 설정(현재 KRW(원화) 가능)
				$nicepayWEB->setParam( "CHARSET", "UTF8" );                       // 인코딩
				$nicepayWEB->setParam( "PayMethod", $payMethod );                  // 결제방법
				$nicepayWEB->setParam( "LicenseKey", $merchantKey );               // 상점키

				//결제요청
				ob_start();
				$responseDTO = $nicepayWEB->doService( $_REQUEST );
				ob_end_clean();

				//결제결과
				$resultCode    = $responseDTO->getParameter( "ResultCode" );     // 결과코드 (정상 결과코드:3001)
				$resultMsg     = $responseDTO->getParameterUTF( "ResultMsg" );   // 결과메시지
				$authDate      = $responseDTO->getParameter( "AuthDate" );       // 승인일시 (YYMMDDHH24mmss)
				$authCode      = $responseDTO->getParameter( "AuthCode" );       // 승인번호
				$buyerName     = $responseDTO->getParameterUTF( "BuyerName" );   // 구매자명
				$mallUserID    = $responseDTO->getParameter( "MallUserID" );     // 회원사고객ID
				$goodsName     = $responseDTO->getParameterUTF( "GoodsName" );   // 상품명
				$mallUserID    = $responseDTO->getParameter( "MallUserID" );     // 회원사ID
				$mid           = $responseDTO->getParameter( "MID" );            // 상점ID
				$tid           = $responseDTO->getParameter( "TID" );            // 거래ID
				$moid          = $responseDTO->getParameter( "Moid" );           // 주문번호
				$amt           = $responseDTO->getParameter( "Amt" );            // 금액
				$cardNo        = $responseDTO->getParameter( "CardNo" );         // 카드번호
				$cardQuota     = $responseDTO->getParameter( "CardQuota" );      // 카드 할부개월 (00:일시불,02:2개월)
				$cardCode      = $responseDTO->getParameter( "CardCode" );       // 결제카드사코드
				$cardName      = $responseDTO->getParameterUTF( "CardName" );    // 결제카드사명
				$bankCode      = $responseDTO->getParameter( "BankCode" );       // 은행코드
				$bankName      = $responseDTO->getParameterUTF( "BankName" );    // 은행명
				$rcptType      = $responseDTO->getParameter( "RcptType" );       // 현금 영수증 타입 (0:발행되지않음,1:소득공제,2:지출증빙)
				$rcptAuthCode  = $responseDTO->getParameter( "RcptAuthCode" );   // 현금영수증 승인번호
				$carrier       = $responseDTO->getParameter( "Carrier" );        // 이통사구분
				$dstAddr       = $responseDTO->getParameter( "DstAddr" );        // 휴대폰번호
				$vbankBankCode = $responseDTO->getParameter( "VbankBankCode" );  // 가상계좌은행코드
				$vbankBankName = $responseDTO->getParameterUTF( "VbankBankName" );  // 가상계좌은행명
				$vbankNum      = $responseDTO->getParameter( "VbankNum" );       // 가상계좌번호
				$vbankExpDate  = $responseDTO->getParameter( "VbankExpDate" );   // 가상계좌입금예정일

				$this->add_log( "결제 승인 결과\n" . print_r( array (
						'resultCode' => $resultCode,
						'resultMsg'  => $resultMsg
					), true )
				);

				//성공시 나이스페이로 결제 성공 전달
				if ( $resultCode == $this->success_code ) {
					$year_prefix = substr( date( "Y" ), 0, 2 );
					pafw_update_meta_data( $order, "_pafw_payment_method", $this->paymethod );
					pafw_update_meta_data( $order, "_pafw_txnid", $moid );
					pafw_update_meta_data( $order, "_pafw_payed_date", $year_prefix . $authDate );
					pafw_update_meta_data( $order, "_pafw_total_price", intval( $amt ) );

					$this->process_standard( $order, $responseDTO );

					$this->payment_complete( $order, $tid );

				} else {
					if ( empty( $resultCode ) && ! empty( $_REQUEST['ResultCode'] ) ) {
						$resultCode = $_REQUEST['ResultCode'];
					}
					if ( empty( $resultMsg ) && ! empty( $_REQUEST['ResultMsg'] ) ) {
						$resultMsg = $_REQUEST['ResultMsg'];
					}

					throw new PAFW_Exception( sprintf( __( '결제 승인 요청 과정에서 오류가 발생했습니다. 관리자에게 문의해주세요. 오류코드(%s), 오류메시지(%s)', 'pgall-for-woocommerce' ), $resultCode, $resultMsg ), '3004', $resultCode );
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
						$order->update_status( 'failed', __( '나이스페이 결제내역을 확인하신 후, 고객에게 연락을 해주시기 바랍니다.', 'pgall-for-woocommerce' ) );
					}
				}

				do_action( 'pafw_payment_fail', $order, ! empty( $error_code ) ? $error_code : $e->getCode(), $e->getMessage() );

				wp_safe_redirect( wc_get_page_permalink( 'checkout' ) );
				die();
			}
		}

		function process_order_pay() {
			wp_send_json_success( $this->get_payment_form() );
		}

		function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			do_action( 'pafw_process_payment', $order );

			return $this->get_payment_form( $order_id, pafw_get_object_property( $order, 'order_key' ) );
		}
		function get_payment_form( $order_id = null, $order_key = null ) {
			try {

				$this->check_requirement();

				$order = $this->get_order( $order_id, $order_key );

				pafw_set_browser_information( $order );
				$this->has_enough_stock( $order );
				$return_url  = $this->get_api_url( 'payment' ); //Return URL 가져오기
				$userid      = get_current_user_id();
				$txnid       = $this->get_txnid( $order );
				$productinfo = $this->make_product_info( $order );
				$order_total = $order->get_total();
				$order->set_payment_method( $this );

				if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
					$order->save();
				}
				$merchantID  = $this->merchant_id;         // 상점아이디
				$merchantKey = $this->merchant_key;        // 상점키
				$goodsCnt    = $order->get_item_count();               // 결제상품개수
				$goodsName   = esc_attr( $productinfo );                 // 결제상품명
				$price       = $order_total;                           // 결제상품금액
				$buyerName   = pafw_get_object_property( $order, 'billing_last_name' ) . pafw_get_object_property( $order, 'billing_first_name' );       // 구매자명
				$buyerTel    = pafw_get_customer_phone_number( $order );      // 구매자연락처
				$buyerEmail  = pafw_get_object_property( $order, 'billing_email' );       // 구매자메일주소
				$moid        = $txnid;                                 // 상품주문번호
				$charset     = 'utf-8';

				$ediDate    = date( "YmdHis" );
				$hashString = bin2hex( hash( 'sha256', $ediDate . $merchantID . $price . $merchantKey, true ) );
				$ip         = $_SERVER['REMOTE_ADDR'];

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
		function process_payment_response() {

			try {
				$this->add_log( 'Process Payment Response : ' . ! empty( $_REQUEST['type'] ) ? $_REQUEST['type'] : '' );

				if ( ! empty( $_REQUEST ) ) {

					header( 'HTTP/1.1 200 OK' );
					header( "Content-Type: text; charset=utf-8" );
					header( "Cache-Control: no-cache" );
					header( "Pragma: no-cache" );

					if ( ! empty( $_REQUEST['type'] ) ) {
						if ( strpos( $_REQUEST['type'], '?' ) !== false ) {
							$return_type      = explode( '?', $_REQUEST['type'] );
							$_REQUEST['type'] = $return_type[0];
						} else {
							$return_type = explode( ',', $_REQUEST['type'] );
						}

						$res_moid = pafw_get( $_REQUEST, 'Moid', pafw_get( $_REQUEST, 'MOID' ) );

						//전달값에서 주문번호 추출
						if ( $res_moid ) {
							$orderid = explode( '_', $res_moid );
						}

						//주문번호 분리하여 주문 로딩
						if ( ! empty( $orderid ) ) {
							$orderid = (int) $orderid[0];
							$order   = wc_get_order( $orderid );
						} else {
							throw new Exception( '주문번호 없음' );
						}

						switch ( $return_type[0] ) {
							case "payment":  //PC 결제 결과 처리
								$this->process_response_payment( $_POST );
								$this->redirect_page( $orderid );
								break;
							case "vbank_noti":  //가상계좌 입금통보 수신
								$this->process_vbank_notification();
								break;
							default :
								if ( empty( $return_type[0] ) ) {
									$this->add_log( 'Request Type 값없음 종료.' . print_r( $_REQUEST, true ) );
									wp_die( __( '결제 요청 실패 : 관리자에게 문의하세요!', 'pgall-for-woocommerce' ) );
								} else {
									do_action( 'nicepay_ajax_response', $return_type[0] );
								}
								break;
						}
					} else {
						throw new Exception( 'Request Type 없음' );
					}
				} else {
					throw new Exception( 'Request 없음' );
				}
			} catch ( Exception $e ) {
				$this->add_log( "[오류] " . $e->getMessage() . "\n" . print_r( $_REQUEST, true ) );
				wp_die( __( '결제 요청 실패 : 관리자에게 문의하세요!', 'pgall-for-woocommerce' ) );
			}
		}
		public static function get_nicepay_log_path() {
			$upload_dir = wp_upload_dir();

			$path = $upload_dir['basedir'] . '/nicepay_log/';
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

		function cancel_payment_request_by_user() {
			do_action( 'pafw_payment_cancel' );
			wp_send_json_success();
		}
		function is_test_key() {
			return in_array( pafw_get( $this->settings, 'merchant_id' ), $this->key_for_test );
		}

		public function get_receipt_popup_params() {
			return array (
				'name'     => 'popupIssue',
				'features' => 'toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=420,height=540'
			);
		}

		public function get_merchant_id() {
			return pafw_get( $this->settings, 'merchant_id' );
		}
	}
}