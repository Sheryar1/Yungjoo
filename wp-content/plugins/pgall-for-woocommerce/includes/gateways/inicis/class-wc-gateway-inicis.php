<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	include_once( 'class-encrypt.php' );

	class WC_Gateway_Inicis extends PAFW_Payment_Gateway {

		protected $payment_method_descriptions = null;

		protected $bank_names = null;

		protected $key_for_test = array (
			'INIpayTest',
			'iniescrow0'
		);

		public function __construct() {
			$this->master_id = 'inicis';

			$this->view_transaction_url = 'https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s';

			$this->pg_title     = __( 'KG 이니시스', 'pgall-for-woocommerce' );
			$this->method_title = __( 'KG 이니시스', 'pgall-for-woocommerce' );

			parent::__construct();
		}
		function __get( $key ) {
			$value = isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : '';

			if ( empty( $value ) && 'signkey' == $key ) {
				$value = 'SU5JTElURV9UUklQTEVERVNfS0VZU1RS';  //INIpayTest 기본값
			}

			return $value;
		}
		public static function enqueue_frontend_script( $simple_pay = 'no' ) {
			?>
            <script type=text/javascript src="https://stdpay.inicis.com/stdjs/INIStdPay.js"></script>
			<?php
		}
		function encrypt_notification( $data, $hash ) {
			$param = array (
				'txnid' => $data,
				'hash'  => $hash
			);

			return aes256_cbc_encrypt( "pgall-for-woocommerce", json_encode( $param ), "CODEMSHOP" );
		}
		function decrypt_notification( $data ) {
			return json_decode( aes256_cbc_decrypt( "pgall-for-woocommerce", $data, "CODEMSHOP" ) );
		}
		function get_payment_description( $paymethod ) {
			switch ( $paymethod ) {
				case "card":
					return __( '신용카드(안심클릭)', 'pgall-for-woocommerce' );
					break;
				case "vcard":
					return __( '신용카드(ISP)', 'pgall-for-woocommerce' );
					break;
				case "directbank":
					return __( '실시간계좌이체', 'pgall-for-woocommerce' );
					break;
				case "wcard":
					return __( '신용카드(모바일)', 'pgall-for-woocommerce' );
					break;
				case "vbank":
					return __( '가상계좌 무통장입금', 'pgall-for-woocommerce' );
					break;
				case "bank":
					return __( '실시간계좌이체(모바일)', 'pgall-for-woocommerce' );
					break;
				case "hpp":
					return __( '휴대폰 소액결제', 'pgall-for-woocommerce' );
					break;
				case "mobile":
					return __( '휴대폰 소액결제(모바일)', 'pgall-for-woocommerce' );
					break;
				case "kpay":
					return __( 'KPAY 간편결제', 'pgall-for-woocommerce' );
					break;
				default:
					return $paymethod;
					break;
			}
		}
		function get_bank_name( $VACT_BankCode = '' ) {
			if ( ! empty( $VACT_BankCode ) ) {
				switch ( $VACT_BankCode ) {
					case "02":
						$VACT_BankCodeName = __( '한국산업은행', 'pgall-for-woocommerce' );
						break;
					case "03":
						$VACT_BankCodeName = __( '기업은행', 'pgall-for-woocommerce' );
						break;
					case "04":
						$VACT_BankCodeName = __( '국민은행', 'pgall-for-woocommerce' );
						break;
					case "05":
						$VACT_BankCodeName = __( '외환은행', 'pgall-for-woocommerce' );
						break;
					case "06":
						$VACT_BankCodeName = __( '국민은행(구,주택은행)', 'pgall-for-woocommerce' );
						break;
					case "07":
						$VACT_BankCodeName = __( '수협중앙회', 'pgall-for-woocommerce' );
						break;
					case "11":
						$VACT_BankCodeName = __( '농협중앙회', 'pgall-for-woocommerce' );
						break;
					case "12":
						$VACT_BankCodeName = __( '단위농협', 'pgall-for-woocommerce' );
						break;
					case "16":
						$VACT_BankCodeName = __( '축협중앙회', 'pgall-for-woocommerce' );
						break;
					case "20":
						$VACT_BankCodeName = __( '우리은행', 'pgall-for-woocommerce' );
						break;
					case "21":
						$VACT_BankCodeName = __( '조흥은행(구)', 'pgall-for-woocommerce' );
						break;
					case "22":
						$VACT_BankCodeName = __( '상업은행', 'pgall-for-woocommerce' );
						break;
					case "23":
						$VACT_BankCodeName = __( '제일은행', 'pgall-for-woocommerce' );
						break;
					case "24":
						$VACT_BankCodeName = __( '한일은행', 'pgall-for-woocommerce' );
						break;
					case "25":
						$VACT_BankCodeName = __( '서울은행', 'pgall-for-woocommerce' );
						break;
					case "26":
						$VACT_BankCodeName = __( '신한은행(구)', 'pgall-for-woocommerce' );
						break;
					case "27":
						$VACT_BankCodeName = __( '씨티은행', 'pgall-for-woocommerce' );
						break;
					case "31":
						$VACT_BankCodeName = __( '대구은행', 'pgall-for-woocommerce' );
						break;
					case "32":
						$VACT_BankCodeName = __( '부산은행', 'pgall-for-woocommerce' );
						break;
					case "34":
						$VACT_BankCodeName = __( '광주은행', 'pgall-for-woocommerce' );
						break;
					case "35":
						$VACT_BankCodeName = __( '제주은행', 'pgall-for-woocommerce' );
						break;
					case "37":
						$VACT_BankCodeName = __( '전북은행', 'pgall-for-woocommerce' );
						break;
					case "38":
						$VACT_BankCodeName = __( '강원은행', 'pgall-for-woocommerce' );
						break;
					case "39":
						$VACT_BankCodeName = __( '경남은행', 'pgall-for-woocommerce' );
						break;
					case "41":
						$VACT_BankCodeName = __( '비씨카드', 'pgall-for-woocommerce' );
						break;
					case "45":
						$VACT_BankCodeName = __( '새마을금고', 'pgall-for-woocommerce' );
						break;
					case "48":
						$VACT_BankCodeName = __( '신용협동조합중앙회', 'pgall-for-woocommerce' );
						break;
					case "50":
						$VACT_BankCodeName = __( '상초저축은행', 'pgall-for-woocommerce' );
						break;
					case "53":
						$VACT_BankCodeName = __( '씨티은행', 'pgall-for-woocommerce' );
						break;
					case "54":
						$VACT_BankCodeName = __( '홍콩상하이은행', 'pgall-for-woocommerce' );
						break;
					case "55":
						$VACT_BankCodeName = __( '도이치은행', 'pgall-for-woocommerce' );
						break;
					case "56":
						$VACT_BankCodeName = __( 'ABN암로', 'pgall-for-woocommerce' );
						break;
					case "57":
						$VACT_BankCodeName = __( 'JP모건', 'pgall-for-woocommerce' );
						break;
					case "59":
						$VACT_BankCodeName = __( '미쓰비시도쿄은행', 'pgall-for-woocommerce' );
						break;
					case "60":
						$VACT_BankCodeName = __( 'BOA(Bank of America)', 'pgall-for-woocommerce' );
						break;
					case "64":
						$VACT_BankCodeName = __( '산림조합', 'pgall-for-woocommerce' );
						break;
					case "70":
						$VACT_BankCodeName = __( '신안상호저축은행', 'pgall-for-woocommerce' );
						break;
					case "71":
						$VACT_BankCodeName = __( '우체국', 'pgall-for-woocommerce' );
						break;
					case "81":
						$VACT_BankCodeName = __( '하나은행', 'pgall-for-woocommerce' );
						break;
					case "83":
						$VACT_BankCodeName = __( '평화은행', 'pgall-for-woocommerce' );
						break;
					case "87":
						$VACT_BankCodeName = __( '신세계', 'pgall-for-woocommerce' );
						break;
					case "88":
						$VACT_BankCodeName = __( '신한은행', 'pgall-for-woocommerce' );
						break;
					case "D1":
						$VACT_BankCodeName = __( '동양종합금융증권', 'pgall-for-woocommerce' );
						break;
					case "D2":
						$VACT_BankCodeName = __( '현대증권', 'pgall-for-woocommerce' );
						break;
					case "D3":
						$VACT_BankCodeName = __( '미래에셋증권', 'pgall-for-woocommerce' );
						break;
					case "D4":
						$VACT_BankCodeName = __( '한국투자증권', 'pgall-for-woocommerce' );
						break;
					case "D5":
						$VACT_BankCodeName = __( '우리투자증권', 'pgall-for-woocommerce' );
						break;
					case "D6":
						$VACT_BankCodeName = __( '하이투자증권', 'pgall-for-woocommerce' );
						break;
					case "D7":
						$VACT_BankCodeName = __( 'HMC투자증권', 'pgall-for-woocommerce' );
						break;
					case "D8":
						$VACT_BankCodeName = __( 'SK증권', 'pgall-for-woocommerce' );
						break;
					case "D9":
						$VACT_BankCodeName = __( '대신증권', 'pgall-for-woocommerce' );
						break;
					case "DA":
						$VACT_BankCodeName = __( '하나대투증권', 'pgall-for-woocommerce' );
						break;
					case "DB":
						$VACT_BankCodeName = __( '굿모닝신한증권', 'pgall-for-woocommerce' );
						break;
					case "DC":
						$VACT_BankCodeName = __( '동부증권', 'pgall-for-woocommerce' );
						break;
					case "DD":
						$VACT_BankCodeName = __( '유진투자증권', 'pgall-for-woocommerce' );
						break;
					case "DE":
						$VACT_BankCodeName = __( '메리츠증권', 'pgall-for-woocommerce' );
						break;
					case "DF":
						$VACT_BankCodeName = __( '신영증권', 'pgall-for-woocommerce' );
						break;
					case "DG":
						$VACT_BankCodeName = __( '대우증권', 'pgall-for-woocommerce' );
						break;
					case "DH":
						$VACT_BankCodeName = __( '삼성증권', 'pgall-for-woocommerce' );
						break;
					case "DI":
						$VACT_BankCodeName = __( '교보증권', 'pgall-for-woocommerce' );
						break;
					case "DJ":
						$VACT_BankCodeName = __( '키움증권', 'pgall-for-woocommerce' );
						break;
					case "DK":
						$VACT_BankCodeName = __( '이트레이드', 'pgall-for-woocommerce' );
						break;
					case "DL":
						$VACT_BankCodeName = __( '솔로몬증권', 'pgall-for-woocommerce' );
						break;
					case "DM":
						$VACT_BankCodeName = __( '한화증권', 'pgall-for-woocommerce' );
						break;
					case "DN":
						$VACT_BankCodeName = __( 'NH증권', 'pgall-for-woocommerce' );
						break;
					case "DO":
						$VACT_BankCodeName = __( '부국증권', 'pgall-for-woocommerce' );
						break;
					case "DP":
						$VACT_BankCodeName = __( 'LIG증권', 'pgall-for-woocommerce' );
						break;
					default:
						$VACT_BankCodeName = sprintf( __( '은행코드(%d)', 'pgall-for-woocommerce' ), $VACT_BankCode );
						break;
				}

				if ( ! empty( $VACT_BankCodeName ) ) {
					return $VACT_BankCodeName;
				} else {
					return '';
				}
			} else {
				return '';
			}
		}
		function get_card_name( $cardcode ) {
			if ( ! empty( $cardcode ) ) {
				switch ( $cardcode ) {
					case "01":
						$cardname = __( '외환카드', 'inicis_payment' );
						break;
					case "03":
						$cardname = __( '롯데카드', 'inicis_payment' );
						break;
					case "04":
						$cardname = __( '현대카드', 'inicis_payment' );
						break;
					case "06":
						$cardname = __( '국민카드', 'inicis_payment' );
						break;
					case "11":
						$cardname = __( 'BC카드', 'inicis_payment' );
						break;
					case "12":
						$cardname = __( '삼성카드', 'inicis_payment' );
						break;
					case "14":
						$cardname = __( '신한카드', 'inicis_payment' );
						break;
					case "15":
						$cardname = __( '한미카드', 'inicis_payment' );
						break;
					case "16":
						$cardname = __( 'NH카드', 'inicis_payment' );
						break;
					case "17":
						$cardname = __( '하나SK카드', 'inicis_payment' );
						break;
					case "21":
						$cardname = __( '해외비자카드', 'inicis_payment' );
						break;
					case "22":
						$cardname = __( '해외마스터카드', 'inicis_payment' );
						break;
					case "23":
						$cardname = __( 'JCB카드', 'inicis_payment' );
						break;
					case "24":
						$cardname = __( '해외아멕스카드', 'inicis_payment' );
						break;
					case "25":
						$cardname = __( '해외다이너스카드', 'inicis_payment' );
						break;
					case "26":
						$cardname = __( '은련카드', 'inicis_payment' );
						break;
					default:
						$cardname = sprintf( __( '카드코드(%d)', 'inicis_payment' ), $cardcode );
						break;
				}

				if ( ! empty( $cardname ) ) {
					return $cardname;
				} else {
					return '';
				}

			} else {
				return '';
			}

		}
		function cancel_request( $order, $msg, $code = "1" ) {
			if ( 'standard' == pafw_get( $this->settings, 'interface_mode', 'standard' ) ) {
				return $this->do_cancel_by_standard( $order, $msg, $code );
			} else {
				return $this->do_cancel_by_gateway( $order, $msg, $code );
			}

		}
		function do_cancel_by_gateway( $order, $msg, $code = "1" ) {
			$response = wp_remote_post( PAFW_Gateway::gateway_url(), array (
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array (),
					'body'        => array (
						'service'        => 'inicis',
						'version'        => '1.0',
						'command'        => 'cancel',
						'domain'         => home_url(),
						'gateway_id'     => pafw_get( $this->settings, 'gateway_id' ),
						'transaction_id' => $this->get_transaction_id( $order ),
						'merchant_id'    => $this->merchant_id,
						'cancel_message' => $msg,
						'cancel_code'    => $code,
					),
					'cookies'     => array ()
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			} else {
				$result = json_decode( $response['body'], true );

				if ( '0000' == pafw_get( $result, 'code' ) ) {
					$data = pafw_get( $result, 'data' );
					if ( "00" == pafw_get( $data, 'code' ) ) {
						do_action( 'pafw_payment_action', 'cancelled', $order->get_total(), $order, $this );

						return "success";
					} else {
						throw new Exception( pafw_get( $data, 'message' ) );
					}
				} else {
					throw new Exception( sprintf( '[%s] %s', pafw_get( $result, 'code' ), pafw_get( $result, 'message' ) ) );
				}
			}
		}
		function do_cancel_by_standard( $order, $msg, $code = "1" ) {
			$transaction_id = $this->get_transaction_id( $order );

			if ( version_compare( PHP_VERSION, '7.1.0' ) < 0 ) {
				require_once( PAFW()->plugin_path() . "/lib/inicis/inipay50/INILib.php" );
			} else {
				require_once( PAFW()->plugin_path() . "/lib/inicis/inipay50_71/INILib.php" );
			}

			$inipay = new INIpay50();

			$inipay->SetField( "inipayhome", $this->settings['libfolder'] );
			$inipay->SetField( "type", "cancel" );
			$inipay->SetField( "debug", "true" );
			$inipay->SetField( "mid", $this->merchant_id );
			$inipay->SetField( "admin", "1111" );
			$inipay->SetField( "tid", $transaction_id );
			$inipay->SetField( "cancelmsg", $msg );

			if ( $code != "" ) {
				$inipay->SetField( "cancelcode", $code );
			}

			$inipay->startAction();

			if ( $inipay->getResult( 'ResultCode' ) == "00" ) {
				do_action( 'pafw_payment_action', 'cancelled', $order->get_total(), $order, $this );

				return "success";
			} else {
				throw new Exception( mb_convert_encoding( $inipay->GetResult( 'ResultMsg' ), "UTF-8", "EUC-KR" ) );
			}
		}
		function make_hash( $order, $user_id, $txnid, $print_log = false ) {
			$order_total        = $order->get_total();
			$billing_first_name = pafw_get_object_property( $order, 'billing_first_name' );
			$billing_email      = pafw_get_object_property( $order, 'billing_email' );
			$product_info       = function_exists( 'icl_object_id' ) ? '' : $this->make_product_info( $order );

			$str = (string) $this->merchant_id . "|$txnid|$user_id|$order_total|$product_info|" . $billing_first_name . "|" . $billing_email . "|||||||||||";

			if ( $print_log ) {
				$this->add_log( $str );
			}

			return hash( 'sha512', $str );
		}
		function process_payment_response_standard( $posted ) {

			$this->check_requirement();

			require_once( PAFW()->plugin_path() . '/lib/inicis/inistd/INIStdPayUtil.php' );
			require_once( PAFW()->plugin_path() . '/lib/inicis/inistd/HttpClient.php' );

			$util = new INIStdPayUtil();

			try {
				$merchantData = $_REQUEST["merchantData"];
				$notification = $this->decrypt_notification( $merchantData );

				if ( empty( $notification ) || empty( $notification->txnid ) ) {
					throw new PAFW_Exception( __( '유효하지않은 주문입니다.', 'pgall-for-woocommerce' ), '1001', 'PAFW-1001' );
				}
				$order = $this->get_order_from_notification( $notification, get_current_user_id() );
				if ( strcmp( "0000", $_REQUEST["resultCode"] ) == 0 ) {
					//성공시 이니시스로 결제 성공 전달
					$mid     = $_REQUEST["mid"];
					$signKey = $this->signkey;

					$timestamp = $util->getTimestamp();
					$charset   = "UTF-8";
					$format    = "JSON";
					$authToken = $_REQUEST["authToken"];
					$authUrl   = $_REQUEST["authUrl"];
					$netCancel = $_REQUEST["netCancelUrl"];

					$signParam["authToken"] = $authToken;  // 필수
					$signParam["timestamp"] = $timestamp;  // 필수
					$signature              = $util->makeSignature( $signParam );

					$authMap["mid"]       = $mid;   // 필수
					$authMap["authToken"] = $authToken; // 필수
					$authMap["signature"] = $signature; // 필수
					$authMap["timestamp"] = $timestamp; // 필수
					$authMap["charset"]   = $charset;
					$authMap["format"]    = $format;

					try {
						$httpUtil = new HttpClient();

						if ( ! $httpUtil->processHTTP( $authUrl, $authMap ) ) {
							throw new PAFW_Exception( sprintf( __( "거래 서버와 통신 실패 : 해당 거래건이 결제가 되었는지 반드시 확인해주세요. - %s", 'pgall-for-woocommerce' ), $httpUtil->errormsg ), '3002', $httpUtil->errorcode );
						}

						$resultMap = json_decode( $httpUtil->body, true );

						$this->add_log( "결제 승인 결과\n" . print_r( array (
								'resultCode' => $resultMap["resultCode"],
								'resultMsg'  => $resultMap["resultMsg"]
							), true )
						);
						if ( $notification->txnid != $resultMap['MOID'] ) {
							throw new PAFW_Exception( sprintf( __( '주문요청(%s, %s)에 대한 위변조 검사 오류입니다. 결제는 처리되었으나, 결제요청에 오류가 있습니다. 이니시스 결제내역을 확인해주세요.', 'pgall-for-woocommerce' ), $notification->txnid, $resultMap['MOID'] ), '3003', 'PAFW-3003' );
						}

						$secureMap["mid"]      = $mid;
						$secureMap["tstamp"]   = $timestamp;
						$secureMap["MOID"]     = $resultMap["MOID"];
						$secureMap["TotPrice"] = $resultMap["TotPrice"];
						$secureSignature       = $util->makeSignatureAuth( $secureMap );

						if ( strcmp( "0000", $resultMap["resultCode"] ) == 0 && strcmp( $secureSignature, $resultMap["authSignature"] ) == 0 ) {
							pafw_update_meta_data( $order, "_pafw_payment_method", $resultMap['payMethod'] );
							pafw_update_meta_data( $order, "_pafw_txnid", $resultMap['MOID'] );
							pafw_update_meta_data( $order, "_pafw_payed_date", $resultMap['applDate'] . $resultMap['applTime'] );
							pafw_update_meta_data( $order, "_pafw_total_price", $resultMap['TotPrice'] );
							$this->process_standard( $order, $resultMap );
							$this->payment_complete( $order, $resultMap['tid'] );

						} else {
							throw new PAFW_Exception( sprintf( __( '결제 승인 요청 과정에서 오류가 발생했습니다. 관리자에게 문의해주세요. 오류코드(%s), 오류메시지(%s)', 'pgall-for-woocommerce' ), esc_attr( $resultMap["resultCode"] ), esc_attr( $resultMap["resultMsg"] ) ), '3004', $resultMap["resultCode"] );
						}

					} catch ( Exception $e ) {
						$netcancelResultString = "";
						if ( $httpUtil->processHTTP( $netCancel, $authMap ) ) {
							$netcancelResultString = $httpUtil->body;
						} else {
							throw new PAFW_Exception( sprintf( __( "거래 서버와 통신 실패 : 해당 거래건이 결제가 되었는지 반드시 확인해주세요. - %s", 'pgall-for-woocommerce' ) . $httpUtil->errormsg ), '3005', $httpUtil->errorcode );
						}

						$netcancelResultString = str_replace( "<", "&lt;", $netcancelResultString );
						$netcancelResultString = str_replace( ">", "&gt;", $netcancelResultString );

						$resultMap = json_decode( $netcancelResultString, true );

						$this->add_log( sprintf( __( "자동 망취소 처리 결과\n%s", 'pgall-for-woocommerce' ), print_r( $resultMap, true ) ) );
						$order->add_order_note( sprintf( __( "자동 망취소 처리 결과\n%s", 'pgall-for-woocommerce' ), print_r( $resultMap, true ) ) );
						wc_add_notice( sprintf( __( '비정상 주문으로 확인되어 자동취소가 진행되었습니다. 자동취소 처리 결과를 확인해주세요. [ 처리결과 : %s ]', 'pgall-for-woocommerce' ), $resultMap['resultMsg'] ), 'error' );
						throw $e;
					}

				} else {
					switch ( $_REQUEST['resultCode'] ) {
						case "V813":
							throw new PAFW_Exception( __( '결제 가능시간(30분) 초과로 인해 자동으로 취소되었습니다. 잠시 후 다시 시도해주세요.', 'pgall-for-woocommerce' ), '3001', $_REQUEST['resultCode'] );
						case "V016":
							throw new PAFW_Exception( __( 'Signkey 가 정확하지 않습니다. 관리자에게 문의하여 주세요. (invalid signkey detected)', 'pgall-for-woocommerce' ), '3001', $_REQUEST['resultCode'] );
						case "V013":
							throw new PAFW_Exception( __( '존재하지 않는 상점아이디 입니다. 관리자에게 문의하여 주세요. (invalid mid detected)', 'pgall-for-woocommerce' ), '3001', $_REQUEST['resultCode'] );
						default:
							throw new PAFW_Exception( sprintf( __( '결제 승인 요청 과정에서 오류가 발생했습니다. 관리자에게 문의해주세요. 오류코드(%s), 오류메시지(%s)', 'pgall-for-woocommerce' ), esc_attr( $_REQUEST["resultCode"] ), esc_attr( $_REQUEST["resultMsg"] ), $_REQUEST['resultCode'] ), '3001', $_REQUEST['resultCode'] );
					}
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
						$order->update_status( 'failed', __( '이니시스 결제내역을 확인하신 후, 고객에게 연락을 해주시기 바랍니다.', 'pgall-for-woocommerce' ) );
					}
				}

				do_action( 'pafw_payment_fail', $order, ! empty( $error_code ) ? $error_code : $e->getCode(), $e->getMessage() );

				$this->redirect_page( $order ? pafw_get_object_property( $order, 'id' ) : '' );
				die();
			}
		}
		function get_order_from_notification( $notification, $user_id = '' ) {

			if ( null == $order = apply_filters( 'pafw_get_order', null, $this->get_order_id_from_txnid( $notification->txnid ) ) ) {
				$order = wc_get_order( $this->get_order_id_from_txnid( $notification->txnid ) );

				if ( ! $order ) {
					throw new PAFW_Exception( __( '유효하지않은 주문입니다.', 'pgall-for-woocommerce' ), '1001', 'PAFW-1001' );
				}
				$this->validate_order_status( $order );
				if ( ! $this->validate_txnid( $order, $notification->txnid ) ) {
					throw new PAFW_Exception ( sprintf( __( '유효하지 않은 주문번호(%s) 입니다.', 'pgall-for-woocommerce' ), $notification->txnid ), '1002', 'PAFW-1002' );
				}

				$hash = $this->make_hash( $order, $user_id, $notification->txnid );
				if ( $notification->hash != $hash ) {
					throw new PAFW_Exception ( sprintf( __( '주문요청(%s)에 대한 위변조 검사 오류입니다.', 'pgall-for-woocommerce' ), $notification->txnid ), '1003', 'PAFW-1003' );
				}
			}

			return $order;
		}
		function process_payment_response_mobile_next( $posted ) {
			try {
				$order = null;

				$this->check_requirement();

				require_once( PAFW()->plugin_path() . "/lib/inicis/inimx/INImx.php" );

				if ( $_REQUEST['P_STATUS'] == '00' ) {
					$notification = $this->decrypt_notification( $_REQUEST['P_NOTI'] );

					if ( empty( $notification ) || empty( $notification->txnid ) ) {
						throw new PAFW_Exception ( sprintf( __( '유효하지 않은 주문번호(%s) 입니다.', 'pgall-for-woocommerce' ), $notification->txnid ), '1002', 'PAFW-1002' );
					}
					$order = $this->get_order_from_notification( $notification );
					$inimx              = new INImx();
					$inimx->reqtype     = "PAY";
					$inimx->inipayhome  = $this->settings['libfolder'];
					$inimx->id_merchant = $this->merchant_id;
					$inimx->status  = $P_STATUS;
					$inimx->rmesg1  = $P_RMESG1;
					$inimx->tid     = $P_TID;
					$inimx->req_url = $P_REQ_URL;
					$inimx->noti    = $P_NOTI;
					$inimx->startAction();
					$inimx->getResult();
					if ( $inimx->m_resultCode != "00" ) {
						$message = sprintf( __( '결제 승인 요청 과정에서 오류가 발생했습니다. 오류코드(%s), 오류메시지(%s)', 'pgall-for-woocommerce' ), $inimx->m_resultCode, mb_convert_encoding( $inimx->m_resultMsg, "UTF-8", "CP949" ) );
						throw new PAFW_Exception ( $message, '1003', $inimx->m_resultCode );
					}

					if ( $notification->txnid != $inimx->m_moid ) {
						throw new PAFW_Exception( sprintf( __( '주문요청(%s, %s)에 대한 위변조 검사 오류입니다. 결제는 처리되었으나, 결제요청에 오류가 있습니다. 이니시스 결제내역을 확인해주세요.', 'pgall-for-woocommerce' ), $notification->txnid, $inimx->m_moid ), '1004', 'PAFW-1004' );
					}
					pafw_update_meta_data( $order, '_pafw_payment_method', $inimx->m_payMethod );
					pafw_update_meta_data( $order, '_pafw_txnid', $inimx->m_moid );
					pafw_update_meta_data( $order, '_pafw_payed_date', $inimx->m_pgAuthDate . $inimx->m_pgAuthTime );
					pafw_update_meta_data( $order, '_pafw_total_price', $inimx->m_resultprice );
					$this->process_mobile_next( $order, $inimx );
					$this->payment_complete( $order, $inimx->m_tid );

					pafw_delete_meta_data( $order, "_ini_rn" );
					pafw_delete_meta_data( $order, "_ini_enctype" );

				} else {
					throw new PAFW_Exception( sprintf( __( '결제 승인 요청 과정에서 오류가 발생했습니다. 오류코드(%s), 오류메시지(%s)', 'pgall-for-woocommerce' ), $_REQUEST['P_STATUS'], mb_convert_encoding( $_REQUEST['P_RMESG1'], "UTF-8", "CP949" ) ), '1005', $_REQUEST['P_STATUS'] );
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
						$order->update_status( 'failed' );
					}
				}

				do_action( 'pafw_payment_fail', $order, ! empty( $error_code ) ? $error_code : $e->getCode(), $e->getMessage() );

				$this->redirect_page( $order ? pafw_get_object_property( $order, 'id' ) : '' );
				die();
			}

		}
		function process_mobile_next( $order, $inimx ) {
			$order->add_order_note( sprintf( __( '주문이 완료되었습니다. 결제방법 : [모바일] %s, 이니시스 거래번호(TID) : <a href="https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s" target=_blank>[영수증 확인]</a>, 몰 고유 주문번호 : %s', 'pgall-for-woocommerce' ), $inimx->m_payMethod, $inimx->m_tid, $inimx->m_moid ) );

			if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
				pafw_reduce_order_stock( $order );
			}

			$order->payment_complete();

			do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );
		}
		function process_mobile_noti( $order = null ) {
			$this->add_log( sprintf( __( '주문이 완료되었습니다. 결제방법 : [모바일] %s, 이니시스 거래번호(TID) : %s, 몰 고유 주문번호 : %s', 'pgall-for-woocommerce' ), $_REQUEST['P_TYPE'], $_REQUEST['P_TID'], $_REQUEST['P_OID'] ) );
			$order->add_order_note( sprintf( __( '주문이 완료되었습니다. 결제방법 : [모바일] %s, 이니시스 거래번호(TID) : <a href="https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s" target=_blank>[영수증 확인]</a>, 몰 고유 주문번호 : %s', 'pgall-for-woocommerce' ), $_REQUEST['P_TYPE'], $_REQUEST['P_TID'], $_REQUEST['P_OID'] ) );
		}
		function send_mobile_noti_result( $result, $order, $message, $error_code = '' ) {
			if ( ! empty( $_REQUEST['P_OID'] ) ) {
				set_transient( $this->id . '_' . $_REQUEST['P_OID'] . '_result', $result, 30 );
				set_transient( $this->id . '_' . $_REQUEST['P_OID'] . '_message', $message, 30 );
			}
			if ( $order ) {
				if ( ! empty( $message ) ) {
					$order->add_order_note( $message );
				}

				if ( 'OK' != $result ) {
					do_action( 'pafw_payment_fail', $order, $error_code, $message );
					if ( empty( pafw_get_object_property( $order, 'paid_date' ) ) ) {
						$order->update_status( 'failed', __( '이니시스 결제내역을 확인하신 후, 고객에게 연락을 해주시기 바랍니다.', 'pgall-for-woocommerce' ) );
					}
				}
			}

			$this->add_log( sprintf( '[%s] %s', $result, $message ) );
			echo 'OK';
			exit();
		}
		function process_payment_response_mobile_noti( $posted ) {
			$PGIP = pafw_get( $_SERVER, 'HTTP_X_FORWARDED_FOR', $_SERVER['REMOTE_ADDR'] );

			if ( $PGIP != "211.219.96.165" && $PGIP != "118.129.210.25" && $PGIP != "183.109.71.153" && $PGIP != "203.238.37.15" ) {
				$this->send_mobile_noti_result( 'FAIL', null, sprintf( __( '잘못된 아이피로 접근하였습니다. IP : %s', 'pgall-for-woocommerce' ), $PGIP ), 'PAFW-9000' );
			}

			if ( $_REQUEST['P_TYPE'] == "VBANK" ) {
				$this->process_mobile_noti();
			} else {
				$notification = $this->decrypt_notification( $_POST['P_NOTI'] );

				if ( empty( $notification ) || empty( $notification->txnid ) ) {
					$this->send_mobile_noti_result( 'FAIL', null, __( '유효하지않은 주문입니다. (invalid notification)', 'pgall-for-woocommerce' ), 'PAFW-9001' );
				}
				try {
					$order = $this->get_order_from_notification( $notification );
				} catch ( Exception $e ) {
					$order   = wc_get_order( $this->get_order_id_from_txnid( $notification->txnid ) );
					$message = sprintf( __( '[PAFW-ERR-%s] %s', 'pgall-for-woocommerce' ), $e->getCode(), $e->getMessage() );
					$this->send_mobile_noti_result( 'FAIL', $order, $message, 'PAFW-9003' );
				}

				if ( $_REQUEST['P_STATUS'] == '00' ) {
					pafw_update_meta_data( $order, '_pafw_payment_method', $_REQUEST['P_TYPE'] );
					pafw_update_meta_data( $order, '_pafw_txnid', $_REQUEST['P_OID'] );
					pafw_update_meta_data( $order, '_pafw_payed_date', $_REQUEST['P_AUTH_DT'] );
					pafw_update_meta_data( $order, '_pafw_total_price', $_REQUEST['P_AMT'] );
					$this->process_mobile_noti( $order );
					$this->payment_complete( $order, $_REQUEST['P_TID'] );

					pafw_delete_meta_data( $order, "_ini_rn" );
					pafw_delete_meta_data( $order, "_ini_enctype" );

					$this->send_mobile_noti_result( 'OK', $order, '' );
				} else {
					$message = sprintf( __( '주문 처리 실패. 결제방법 : [모바일] %s, 이니시스 거래번호(TID) : %s, 몰 고유 주문번호 : %s, 에러코드 : %s, 에러내용 : %s', 'pgall-for-woocommerce' ), $_REQUEST['P_TYPE'], $_REQUEST['P_TID'], $_REQUEST['P_OID'], $_REQUEST['P_STATUS'], mb_convert_encoding( $_REQUEST['P_RMESG1'], "UTF-8", "CP949" ) );
					$this->send_mobile_noti_result( 'FAIL', $order, $message, $_REQUEST['P_STATUS'] );
				}
			}
		}
		function successful_request_mobile_return( $oid ) {
			if ( wp_is_mobile() ) {
				$oid     = explode( '=', $oid );
				$oid     = $oid[1];
				$result  = get_transient( $this->id . '_' . $oid . '_result' );
				$message = get_transient( $this->id . '_' . $oid . '_message' );

				$order_id = explode( '_', $oid );
				$order_id = $order_id[0];

				if ( empty( $result ) ) {
					$retry = empty( $_REQUEST['retry'] ) ? 0 : $_REQUEST['retry'];

					if ( $retry <= 5 ) {
						sleep( 2 );
						$redirect_url = remove_query_arg( 'retry', $_SERVER['REQUEST_URI'] );
						$redirect_url = add_query_arg( 'retry', $_REQUEST['retry'] + 1, $redirect_url );
						header( "Location: " . $redirect_url );
						die();
					}

					do_action( 'pafw_payment_fail', null, 'PAFW-8001', __( '결제를 취소하였거나 처리가 늦어지고 있습니다. 잠시만 기다리셨다가 주문 상태를 다시 확인해주세요. (ERROR: 0xF53D)', 'inicis_payment' ) );

					wc_add_notice( __( '결제를 취소하였거나 처리가 늦어지고 있습니다. 잠시만 기다리셨다가 주문 상태를 다시 확인해주세요. (ERROR: 0xF53D)', 'inicis_payment' ), 'error' );

					$this->redirect_page( $order_id );
					exit();
				} else {
					delete_transient( $this->id . '_' . $oid . '_result' );
					delete_transient( $this->id . '_' . $oid . '_message' );
					if ( 'FAIL' == $result ) {
						if ( ! empty( $message ) ) {
							do_action( 'pafw_payment_fail', null, 'PAFW-8002', $message );
							wc_add_notice( $message, 'error' );
							wc_add_notice( '결제는 성공했지만 주문 처리중 오류가 발생했을 수 있습니다. 관리자에게 문의해주세요.', 'error' );
						}
						$this->redirect_page( $order_id );
						exit();
					}
				}
			}

		}
		function check_requirement() {

			parent::check_requirement();

			if ( ! file_exists( PAFW()->plugin_path() . "/lib/inicis/inistd/INIStdPayUtil.php" ) ) {
				throw new Exception( __( '[ERR-PAFW-0003] INIStdPayUtil.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ) );
			}
			if ( ! file_exists( PAFW()->plugin_path() . "/lib/inicis/inistd/HttpClient.php" ) ) {
				throw new Exception( __( '[ERR-PAFW-0003] HttpClient.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ) );
			}

			if ( ! file_exists( PAFW()->plugin_path() . "/lib/inicis/inimx/INImx.php" ) ) {
				throw new Exception( __( '[ERR-PAFW-0003] INImx.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ) );
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

				require_once( PAFW()->plugin_path() . '/lib/inicis/inistd/INIStdPayUtil.php' );

				$order = $this->get_order( $order_id, $order_key );

				pafw_set_browser_information( $order );
				$this->has_enough_stock( $order );

				$SignatureUtil = new INIStdPayUtil();

				$signKey   = $this->signkey;
				$timestamp = $SignatureUtil->getTimestamp(); //타임스탬프

				//결제옵션 가져오기
				$acceptmethod = $this->get_accpetmethod();

				if ( 'yes' == pafw_get( $this->settings, 'use_nointerest' ) ) {
					$cardNoInterestQuota = $this->nointerest;  //카드무이자 여부 설정
				} else {
					$cardNoInterestQuota = '';
				}

				//가맹점에서 사용할 할부 개월수 설정 (PC 웹용)
				$quotabase_arr    = explode( ',', $this->quotabase );
				$quotabase_option = array ();

				foreach ( $quotabase_arr as $item ) {
					$quotabase_option[] = sprintf( '%02d', (int) $item );
				}
				sort( $quotabase_option );
				$cardQuotaBase = implode( ':', $quotabase_option );

				$mKey = $SignatureUtil->makeHash( $signKey, "sha256" );

				$userid      = get_current_user_id();
				$txnid       = $this->get_txnid( $order );
				$productinfo = $this->make_product_info( $order );
				$price       = $order->get_total();
				$order_total = $order->get_total();
				$order->set_payment_method( $this );

				if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
					$order->save();
				}

				$params = array (
					"oid"       => $txnid,
					"price"     => $price,
					"timestamp" => $timestamp
				);

				$sign         = $SignatureUtil->makeSignature( $params, "sha256" );
				$payView_type = wp_is_mobile() ? '' : 'overlay';
				$hash         = $this->make_hash( $order, wp_is_mobile() ? '' : get_current_user_id(), $txnid );
				$notification = $this->encrypt_notification( $txnid, $hash );

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
		function successful_request_cancelled( $posted ) {

			if ( version_compare( PHP_VERSION, '7.1.0' ) < 0 ) {
				require_once( PAFW()->plugin_path() . "/lib/inicis/inipay50/INILib.php" );
			} else {
				require_once( PAFW()->plugin_path() . "/lib/inicis/inipay50_71/INILib.php" );
			}

			$inipay = new INIpay50();

			$inipay->SetField( "inipayhome", $this->settings['libfolder'] );
			$inipay->SetField( "type", "cancel" );
			$inipay->SetField( "debug", "false" );
			$inipay->SetField( "mid", $_REQUEST['mid'] );
			$inipay->SetField( "admin", "1111" );
			$inipay->SetField( "tid", $_REQUEST['tid'] );
			$inipay->SetField( "cancelmsg", $_REQUEST['msg'] );

			if ( $code != "" ) {
				$inipay->SetField( "cancelcode", $_REQUEST['code'] );
			}

			$inipay->startAction();

			if ( $inipay->getResult( 'ResultCode' ) == "00" ) {
				echo "success";

				return;
				//exit();
			} else {
				echo $inipay->getResult( 'ResultMsg' );

				return;
				//exit();
			}
		}
		function process_payment_response() {
			$this->add_log( "Process Payment Response : " . $_REQUEST['type'] );

			if ( ! empty( $_REQUEST ) ) {

				if ( ! empty( $_REQUEST['type'] ) ) {
					switch ( $_REQUEST['type'] ) {
						//웹표준 결제 추가 호출 경로
						case "std_cancel" :
							do_action( 'pafw_payment_cancel' );

							wp_print_scripts( 'jquery' );
							?>
                            <script language="javascript">
                                parent.jQuery( parent.document.body ).trigger( 'inicis_unblock_payment' );
                            </script>
                            <script language="javascript" type="text/javascript" src="https://stdpay.inicis.com/stdjs/INIStdPay_close.js" charset="UTF-8"></script>
							<?php
							die();
							break;
						case "std_popup" :
							echo '<script language="javascript" type="text/javascript" src="https://stdpay.inicis.com/stdjs/INIStdPay_popup.js" charset="UTF-8"></script>';
							die();
							break;
						default:
							break;
					}
				}

				header( 'HTTP/1.1 200 OK' );
				header( "Content-Type: text; charset=euc-kr" );
				header( "Cache-Control: no-cache" );
				header( "Pragma: no-cache" );

				if ( ! empty( $_REQUEST['type'] ) ) {
					if ( strpos( $_REQUEST['type'], '?' ) !== false ) {
						$return_type          = explode( '?', $_REQUEST['type'] );
						$_REQUEST['type']     = $return_type[0];
						$tmp_status           = explode( '=', $return_type[1] );
						$_REQUEST['P_STATUS'] = $tmp_status[1];
					} else {
						$return_type = explode( ',', $_REQUEST['type'] );
					}

					$res_txnid       = empty( $_REQUEST['txnid'] ) ? '' : $_REQUEST['txnid'];
					$res_p_noti      = empty( $_REQUEST['P_NOTI'] ) ? '' : $_REQUEST['P_NOTI'];
					$res_p_oid       = empty( $_REQUEST['P_OID'] ) ? '' : $_REQUEST['P_OID'];
					$res_oid         = empty( $_REQUEST['oid'] ) ? '' : $_REQUEST['oid'];
					$res_no_oid      = empty( $_REQUEST['no_oid'] ) ? '' : $_REQUEST['no_oid'];
					$res_ordernumber = empty( $_REQUEST['orderNumber'] ) ? '' : $_REQUEST['orderNumber'];
					$res_postid      = empty( $_REQUEST['postid'] ) ? '' : $_REQUEST['postid'];


					if ( $res_txnid ) {
						$orderid = explode( '_', $res_txnid );
					} else if ( $res_p_noti ) {
						$notification = $this->decrypt_notification( $res_p_noti );
						$orderid      = explode( '_', $notification->txnid );
					} else if ( $res_p_oid ) {
						$orderid = explode( '_', $res_p_oid );
					} else if ( $res_oid ) {
						$orderid = explode( '_', $res_oid );
					} else if ( $res_no_oid ) {
						$orderid = explode( '_', $res_no_oid );
					} else if ( $res_ordernumber ) {
						$orderid = explode( '_', $res_ordernumber );
					} else if ( $res_postid ) {
						$orderid = explode( '_', $res_postid );
					} else if ( $return_type[1] ) {
						$temp_oid = explode( '=', $return_type[1] );
						$orderid  = explode( '_', $temp_oid[1] );
					}

					if ( ! empty( $orderid ) ) {
						$orderid = $orderid[0];
						$order   = wc_get_order( $orderid );
						$order   = apply_filters( 'pafw_get_order', $order, $orderid );
					} else {
						$this->add_log( "[오류] 주문번호 없음.\n" . print_r( $_REQUEST, true ) );
						die();
					}

					if ( ! in_array( $return_type[0], array ( 'vbank_refund_add', 'vbank_refund_modify', 'vbank_noti' ) ) && ! empty( $order ) ) {

						$is_mobile_noti = in_array( $return_type[0], array ( 'mobile_noti' ) );
						$p_type         = isset( $_REQUEST['P_TYPE'] ) ? $_REQUEST['P_TYPE'] : '';
						$p_status       = isset( $_REQUEST['P_STATUS'] ) ? $_REQUEST['P_STATUS'] : '';

						//모바일 가상계좌 채번시 노티가 아닌 경우 진행
						if ( $is_mobile_noti && $p_type == 'VBANK' && $p_status == '00' ) {
							$this->add_log( '[처리종료] 모바일 가상계좌 채번 알림' );

							return;
						}

						if ( ! $is_mobile_noti && $p_type != 'VBANK' && $p_status != '02' ) {
							try {
								$this->has_enough_stock( $order );
							} catch ( Exception $e ) {
								wc_add_notice( $e->getMessage(), 'error' );
								$this->add_payment_log( $order, '[오류]', $e->getMessage() );

								$this->redirect_page( $orderid );
							}
						}
					}

					switch ( $return_type[0] ) {
						case "cancelled" :
							$this->successful_request_cancelled( $_POST );
							do_action( 'after_successful_request_cancelled' );
							$this->redirect_page( $orderid );
							break;
						case "vbank_noti" :
							$this->process_vbank_nofi( $_POST );
							do_action( 'after_successful_request_vbank_noti' );
							$this->redirect_page( $orderid );
							break;
						case "mobile_next" :
							$this->process_payment_response_mobile_next( $_POST );
							do_action( 'after_process_payment_response_mobile_next' );
							$this->redirect_page( $orderid );
							break;
						case "mobile_noti" :
							$this->process_payment_response_mobile_noti( $_POST );
							do_action( 'after_process_payment_response_mobile_noti' );
							$this->redirect_page( $orderid );
							break;
						case "mobile_return" :
							$this->successful_request_mobile_return( $return_type[1] );
							do_action( 'after_successful_request_mobile_return' );
							$this->redirect_page( $orderid );
							break;
						case "cancel_payment" :
							do_action( "valid-inicis-request_cancel_payment", $_POST );
							$this->redirect_page( $orderid );
							break;
						case "std":
							$this->process_payment_response_standard( $_POST );
							do_action( 'after_process_payment_response_standard' );
							$this->redirect_page( $orderid );
							break;
						default :
							if ( empty( $return_type[0] ) ) {
								$this->add_log( '[처리종료] Request Type이 올바르지 않음.' );
								wp_die( __( '결제 요청 실패 : 관리자에게 문의하세요!', 'pgall-for-woocommerce' ) );
							} else {
								do_action( 'inicis_ajax_response', $return_type[0] );
							}
							break;
					}
				} else {
					$this->add_log( '[처리종료] Request Type이 올바르지 않음.' );
					wp_die( __( '결제 요청 실패 : 관리자에게 문의하세요!', 'pgall-for-woocommerce' ) );
				}
			} else {
				$this->add_log( '[처리종료] 올바르지 않은 요청입니다.' );
				wp_die( __( '결제 요청 실패 : 관리자에게 문의하세요!', 'pgall-for-woocommerce' ) );
			}
		}
		function get_accpetmethod() {
			//옵션값 기준으로 옵션 설정
			$arr_accept_method = array ();

			if ( ! wp_is_mobile() ) {
				if ( $this->settings['skin_indx'] != '' ) {
					$arr_accept_method[] = 'SKIN(' . $this->settings['skin_indx'] . ')';
				}
			}

			if ( $this->id == 'inicis_stdcard' && ! empty( $this->settings ) ) {
				if ( wp_is_mobile() ) {
					$arr_accept_method[] = 'twotrs_isp=Y';  //신용카드 거래 기본값
					$arr_accept_method[] = 'block_isp=Y';   //신용카드 거래 기본값
					$arr_accept_method[] = 'twotrs_isp_noti=N'; //신용카드 거래 기본값
					$arr_accept_method[] = 'ismart_use_sign=Y'; //30만원 이상 결제 허용
					$arr_accept_method[] = 'apprun_check=Y';    //카드사 앱사용 체크
					$arr_accept_method[] = 'extension_enable=Y';    //사파리 이슈 해결 코드(제3공급자기능활성화)

					if ( 'yes' == pafw_get( $this->settings, 'use_nointerest' ) && ! empty( pafw_get( $this->settings, 'nointerest' ) ) ) {
						$arr_accept_method[] = 'merc_noint=Y';  //상점 무이자할부 설정

						$quotabase_arr    = explode( ',', $this->settings['nointerest'] );
						$quotabase_option = array ();

						foreach ( $quotabase_arr as $item ) {
							$quotabase_option[] = sprintf( '%02d', (int) $item );
						}
						sort( $quotabase_option );
						$quotabase_option = implode( ':', $quotabase_option );


						$noint_quota_tmp     = str_replace( ',', '^', $this->settings['nointerest'] );
						$arr_accept_method[] = 'noint_quota=' . $noint_quota_tmp;  //상점 무이자할부 카드사별 기간값 지정
					}

					if ( $this->settings['cardpoint'] == 'yes' ) {
						$arr_accept_method[] = 'cp_yn=Y';
					}
					$acceptmethod = implode( "&", $arr_accept_method );
				} else {
					if ( $this->settings['cardpoint'] == 'yes' ) {
						$arr_accept_method[] = 'cardpoint';
					}
					$acceptmethod = implode( ":", $arr_accept_method );
				}
			} else if ( $this->id == 'inicis_stdsamsungpay' && ! empty( $this->settings ) ) {
				if ( wp_is_mobile() ) {
					//모바일 옵션
					$arr_accept_method[] = 'twotrs_isp=Y';  //신용카드 거래 기본값
					$arr_accept_method[] = 'block_isp=Y';   //신용카드 거래 기본값
					$arr_accept_method[] = 'd_samsungpay=Y';    //삼성페이 활성화(모바일전용)
					$arr_accept_method[] = 'twotrs_isp_noti=N'; //신용카드 거래 기본값
					$arr_accept_method[] = 'ismart_use_sign=Y'; //30만원 이상 결제 허용
					$arr_accept_method[] = 'apprun_check=Y';    //카드사 앱사용 체크
					$arr_accept_method[] = 'extension_enable=Y';    //사파리 이슈 해결 코드(제3공급자기능활성화)

					if ( 'yes' == pafw_get( $this->settings, 'use_nointerest' ) && ! empty( pafw_get( $this->settings, 'nointerest' ) ) ) {
						$arr_accept_method[] = 'merc_noint=Y';  //상점 무이자할부 설정

						$quotabase_arr    = explode( ',', $this->settings['nointerest'] );
						$quotabase_option = array ();

						foreach ( $quotabase_arr as $item ) {
							$quotabase_option[] = sprintf( '%02d', (int) $item );
						}
						sort( $quotabase_option );
						$quotabase_option = implode( ':', $quotabase_option );


						$noint_quota_tmp     = str_replace( ',', '^', $this->settings['nointerest'] );
						$arr_accept_method[] = 'noint_quota=' . $noint_quota_tmp;  //상점 무이자할부 카드사별 기간값 지정
					}

					if ( $this->settings['cardpoint'] == 'yes' ) {
						$arr_accept_method[] = 'cp_yn=Y';
					}
					$acceptmethod = implode( "&", $arr_accept_method );
				} else {
					//PC 삼성 페이 옵션
					$arr_accept_method[] = 'cardonly';
					if ( $this->settings['cardpoint'] == 'yes' ) {
						$arr_accept_method[] = 'cardpoint';
					}
					$acceptmethod = implode( ":", $arr_accept_method );
				}
			} else if ( $this->id == 'inicis_stdvbank' && ! empty( $this->settings ) ) {
				if ( wp_is_mobile() ) {
					if ( $this->settings['receipt'] == 'yes' ) {
						$arr_accept_method[] = 'vbank_receipt=Y';
					}
					$acceptmethod = implode( "&", $arr_accept_method );
				} else {

					if ( $this->settings['receipt'] == 'yes' ) {
						$arr_accept_method[] = 'va_receipt';    //현금영수증 발급UI 옵션
					}
					if ( $this->settings['receipt'] == 'no' ) {
						$arr_accept_method[] = 'no_receipt';    //현금영수증 미발급 옵션
					}

					$date_limit          = pafw_get( $this->settings, 'account_date_limit', 3 );
					$date                = date( 'Ymd', strtotime( current_time( 'mysql' ) . " +" . $date_limit . " days" ) );
					$arr_accept_method[] = "vbank({$date})";

					$acceptmethod = implode( ":", $arr_accept_method );
				}
			} else if ( $this->id == 'inicis_stdhpp' && ! empty( $this->settings ) ) {
				if ( ! empty( $this->settings['hpp_method'] ) ) {
					$arr_accept_method[] = 'HPP(' . $this->settings['hpp_method'] . ')';
				} else {
					$arr_accept_method[] = 'HPP(2)';
				}
				$acceptmethod = implode( ":", $arr_accept_method );
			} else if ( $this->id == 'inicis_stdkpay' && ! empty( $this->settings ) ) {
				if ( wp_is_mobile() ) {
					$arr_accept_method[] = 'd_kpay=Y';
					$arr_accept_method[] = 'kpay_siteId=KPAY';

					if ( ! empty( $this->settings['direct_run'] ) && 'yes' == $this->settings['direct_run'] ) {
						$arr_accept_method[] = 'd_kpay_app=Y';
					}
					$acceptmethod = implode( "&", $arr_accept_method );
				} else {
					$acceptmethod = implode( "&", $arr_accept_method );
				}
			} else if ( $this->id == 'inicis_stdescrow_bank' && ! empty( $this->settings ) ) {
				if ( $this->settings['receipt'] == 'no' ) {
					$arr_accept_method[] = 'no_receipt';
				}
				$acceptmethod = implode( ":", $arr_accept_method );
			} else if ( $this->id == 'inicis_stdcard' && ! empty( $this->settings ) ) {
				if ( $this->settings['cardpoint'] == 'yes' ) {
					$arr_accept_method[] = 'cardpoint';
				}
				$acceptmethod = implode( ":", $arr_accept_method );
			} else if ( $this->id == 'inicis_stdbank' && ! empty( $this->settings ) ) {
				if ( $this->settings['receipt'] == 'no' ) {
					$arr_accept_method[] = 'no_receipt';
				}
				$acceptmethod = implode( ":", $arr_accept_method );
			} else {
				$acceptmethod = '';
			}

			return $acceptmethod;
		}
		function is_fully_refundable( $order, $screen = 'admin' ) {
			$repay_info = pafw_get_meta( $order, '_inicis_repay' );
			$repay_cnt  = count( json_decode( $repay_info, true ) );

			return parent::is_fully_refundable( $order, $screen ) && $repay_cnt == 0;
		}
		function process_standard( $order, $result_map ) {
			$this->add_log( sprintf( __( '주문이 완료되었습니다. 결제방법 : [웹표준결제] %s, 이니시스 거래번호(TID) : %s, 몰 고유 주문번호 : %s', 'pgall-for-woocommerce' ), $result_map['payMethod'], $result_map['tid'], $result_map['MOID'] ) );
			$order->add_order_note( sprintf( __( '주문이 완료되었습니다. 결제방법 : [웹표준결제] %s, 이니시스 거래번호(TID) : <a href="https://iniweb.inicis.com/app/publication/apReceipt.jsp?noMethod=1&noTid=%s" target=_blank>[영수증 확인]</a>, 몰 고유 주문번호 : %s', 'pgall-for-woocommerce' ), $result_map['payMethod'], $result_map['tid'], $result_map['MOID'] ) );

			if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
				pafw_reduce_order_stock( $order );
			}

			$order->payment_complete();

			do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );
		}
		function is_test_key() {
			return in_array( $this->merchant_id, $this->key_for_test );
		}

		public function get_receipt_popup_params() {
			return array (
				'name'     => 'showreceipt',
				'features' => 'width=410,height=540, scrollbars=no,resizable=no'
			);
		}

		public function get_merchant_id() {
			return pafw_get( $this->settings, 'merchant_id' );
		}
	}
}