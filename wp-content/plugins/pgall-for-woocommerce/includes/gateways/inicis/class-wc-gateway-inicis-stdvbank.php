<?php

//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Inicis_StdVbank' ) ) {

		class WC_Gateway_Inicis_StdVbank extends WC_Gateway_Inicis {

			public function __construct() {
				$this->id = 'inicis_stdvbank';

				parent::__construct();

				$this->settings['gopaymethod'] = 'Vbank';
				$this->settings['paymethod']   = 'vbank';

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '가상계좌 무통장입금', 'pgall-for-woocommerce' );
					$this->description = __( '가상계좌 안내를 통해 무통장입금을 할 수 있습니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}
				$this->supports[] = 'pafw-vbank';
				$this->supports[] = 'pafw-vbank-refund';
			}

			function get_vbank_list() {
				return array (
					"02" => "산업(02)",
					"03" => "기업(03)",
					"04" => "국민(04)",
					"05" => "외환(05)",
					"06" => "국민(주택)(06)",
					"07" => "수협(07)",
					"11" => "농협(11)",
					"12" => "농협(12)",
					"16" => "농협(축협)(16)",
					"20" => "우리(20)",
					"21" => "조흥(21)",
					"23" => "제일(23)",
					"25" => "서울(25)",
					"26" => "신한(26)",
					"27" => "한미(27)",
					"31" => "대구(31)",
					"32" => "부산(32)",
					"34" => "광주(34)",
					"35" => "제주(35)",
					"37" => "전북(37)",
					"38" => "강원(38)",
					"39" => "경남(39)",
					"41" => "비씨(41)",
					"45" => "새마을(45)",
					"48" => "신협(48)",
					"50" => "상호저축은행(50)",
					"53" => "씨티(53)",
					"54" => "홍콩상하이은행(54)",
					"55" => "도이치(55)",
					"56" => "ABN암로(56)",
					"70" => "신안상호(70)",
					"71" => "우체국(71)",
					"81" => "하나(81)",
					"87" => "신세계(87)",
					"88" => "신한(88)",
				);
			}
			function process_standard( $order, $result_map ) {
				pafw_update_meta_data( $order, '_pafw_vacc_tid', $result_map['tid'] );  //입금계좌번호
				pafw_update_meta_data( $order, '_pafw_vacc_num', $result_map['VACT_Num'] );  //입금계좌번호
				pafw_update_meta_data( $order, '_pafw_vacc_bank_code', $result_map['VACT_BankCode'] );    //입금은행코드
				pafw_update_meta_data( $order, '_pafw_vacc_bank_name', $result_map['vactBankName'] );    //입금은행명/코드
				pafw_update_meta_data( $order, '_pafw_vacc_holder', $result_map['VACT_Name'] );    //예금주
				pafw_update_meta_data( $order, '_pafw_vacc_depositor', $result_map['VACT_InputName'] );   //송금자
				pafw_update_meta_data( $order, '_pafw_vacc_date', $result_map['VACT_Date'] . $result_map['VACT_Time'] );    //입금예정일

				$this->add_payment_log( $order, '[ 가상계좌 입금 대기중 ]', array (
					'거래번호' => $result_map['tid']
				) );

				//가상계좌 주문 접수시 재고 차감여부 확인
				pafw_reduce_order_stock( $order );

				$order->update_status( $this->settings['order_status_after_vbank_payment'] );

				//WC 3.0 postmeta update 로 인해 별도로 가상계좌 추가 처리
				if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
					$order->set_date_paid( null );
					$order->save();
				}
			}
			function process_mobile_next( $order, $inimx ) {
				$VACT_ResultMsg = mb_convert_encoding( $inimx->m_resultMsg, "UTF-8", "CP949" );
				$VACT_Name      = mb_convert_encoding( $inimx->m_nmvacct, "UTF-8", "CP949" );
				$VACT_InputName = mb_convert_encoding( $inimx->m_buyerName, "UTF-8", "CP949" );
				$TID            = $inimx->m_tid;
				$MOID           = $inimx->m_moid;
				$VACT_Num       = $inimx->m_vacct;
				$VACT_BankCode  = $inimx->m_vcdbank;

				$VACT_BankCodeName = $this->get_bank_name( $VACT_BankCode );
				$VACT_Date         = $inimx->m_dtinput;
				$VACT_Time         = $inimx->m_tminput;

				pafw_update_meta_data( $order, '_pafw_vacc_tid', $TID );  //입금계좌번호
				pafw_update_meta_data( $order, '_pafw_vacc_num', $VACT_Num );  //입금계좌번호
				pafw_update_meta_data( $order, '_pafw_vacc_bank_code', $VACT_BankCode );    //입금은행코드
				pafw_update_meta_data( $order, '_pafw_vacc_bank_name', $VACT_BankCodeName );    //입금은행명/코드
				pafw_update_meta_data( $order, '_pafw_vacc_holder', $VACT_Name );    //예금주
				pafw_update_meta_data( $order, '_pafw_vacc_depositor', $VACT_InputName );   //송금자
				pafw_update_meta_data( $order, '_pafw_vacc_date', $VACT_Date . $VACT_Time );    //입금예정일

				$this->add_payment_log( $order, '[ 가상계좌 입금 대기중 ]', array (
					'거래번호' => $TID
				) );

				pafw_reduce_order_stock( $order );

				$order->update_status( $this->settings['order_status_after_vbank_payment'] );

				if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
					$order->set_date_paid( null );
					$order->save();
				}
			}
			public function process_mobile_noti( $order = null ) {
				$P_TID     = isset( $_REQUEST['P_TID'] ) ? $_REQUEST['P_TID'] : '';
				$P_MID     = isset( $_REQUEST['P_MID'] ) ? $_REQUEST['P_MID'] : '';
				$P_AUTH_DT = isset( $_REQUEST['P_AUTH_DT'] ) ? $_REQUEST['P_AUTH_DT'] : '';
				$P_STATUS  = isset( $_REQUEST['P_STATUS'] ) ? $_REQUEST['P_STATUS'] : '';
				$P_TYPE    = isset( $_REQUEST['P_TYPE'] ) ? $_REQUEST['P_TYPE'] : '';
				$P_OID     = isset( $_REQUEST['P_OID'] ) ? $_REQUEST['P_OID'] : '';
				$P_FN_CD1  = isset( $_REQUEST['P_FN_CD1'] ) ? $_REQUEST['P_FN_CD1'] : '';
				$P_FN_CD2  = isset( $_REQUEST['P_FN_CD2'] ) ? $_REQUEST['P_FN_CD2'] : '';
				$P_FN_NM   = isset( $_REQUEST['P_FN_NM'] ) ? $_REQUEST['P_FN_NM'] : '';
				$P_AMT     = isset( $_REQUEST['P_AMT'] ) ? $_REQUEST['P_AMT'] : '';
				$P_UNAME   = isset( $_REQUEST['P_UNAME'] ) ? $_REQUEST['P_UNAME'] : '';
				$P_RMESG1  = isset( $_REQUEST['P_RMESG1'] ) ? $_REQUEST['P_RMESG1'] : '';
				$P_RMESG2  = isset( $_REQUEST['P_RMESG2'] ) ? $_REQUEST['P_RMESG2'] : '';
				$P_NOTI    = isset( $_REQUEST['P_NOTI'] ) ? $_REQUEST['P_NOTI'] : '';
				$P_AUTH_NO = isset( $_REQUEST['P_AUTH_NO'] ) ? $_REQUEST['P_AUTH_NO'] : '';

				$this->add_log( '[모바일] 모바일 가상계좌 입금통보 시작 : ' . $P_TID );

				if ( $P_STATUS == "02" ) {
					//OID 에서 주문번호 확인
					$arr_oid    = explode( '_', $P_OID );
					$order_id   = $arr_oid[0];
					$order_date = $arr_oid[1];
					$order_time = $arr_oid[2];
					$order      = wc_get_order( $order_id );

					//$P_RMESG1 에서 입금계좌 및 입금예정일 확인
					$arr_tmp            = explode( '|', $P_RMESG1 );
					$p_vacct_no_tmp     = explode( '=', $arr_tmp[0] );
					$p_vacct_no         = $p_vacct_no_tmp[1];
					$p_exp_datetime_tmp = explode( '=', $arr_tmp[1] );
					$p_exp_datetime     = $p_exp_datetime_tmp[1];

					$txnid             = pafw_get_meta( $order, '_pafw_txnid' );  //상점거래번호(OID)
					$tid               = pafw_get_meta( $order, '_pafw_vacc_tid' );
					$VACT_Num          = pafw_get_meta( $order, '_pafw_vacc_num' );  //입금계좌번호
					$VACT_BankCode     = pafw_get_meta( $order, '_pafw_vacc_bank_code' );    //입금은행코드
					$VACT_BankCodeName = pafw_get_meta( $order, '_pafw_vacc_bank_name' );    //입금은행명/코드
					$VACT_Name         = pafw_get_meta( $order, '_pafw_vacc_holder' );    //예금주
					$VACT_InputName    = pafw_get_meta( $order, '_pafw_vacc_depositor' );   //송금자
					$VACT_Date         = pafw_get_meta( $order, '_pafw_vacc_date' );    //입금예정일

					if ( ! in_array( $order->get_status(), array ( 'completed', 'cancelled', 'refunded' ) ) ) {  //주문상태 확인
						if ( $txnid != $P_OID ) {    //거래번호(oid) 체크
							$this->add_log( "[모바일] 모바일 가상계좌 입금통보 실패 : 거래번호 확인 실패\n" . print_r( $_REQUEST, true ) );
							echo 'FAIL_M11';
							exit();
						}
						if ( $P_FN_CD1 != $VACT_BankCode ) {    //입금은행 코드 체크
							$this->add_log( "[모바일] 모바일 가상계좌 입금통보 실패 : 입금은행 코드 확인 실패\n" . print_r( $_REQUEST, true ) );
							echo 'FAIL_M12';
							exit();
						}
						if ( $VACT_Num != $p_vacct_no ) {    //입금계좌번호 체크
							$this->add_log( "[모바일] 모바일 가상계좌 입금통보 실패 : 입금 계좌번호 확인 실패\n" . print_r( $_REQUEST, true ) );
							echo 'FAIL_M13';
							exit();
						}
						if ( (int) $P_AMT != (int) $order->get_total() ) {    //입금액 체크
							$this->add_log( "[모바일] 모바일 가상계좌 입금통보 실패 : 입금액 확인 실패\n" . print_r( $_REQUEST, true ) );
							echo 'FAIL_M14';
							exit();
						}

						pafw_update_meta_data( $order, '_pafw_vbank_noti_received', 'yes' );
						pafw_update_meta_data( $order, '_pafw_vbank_noti_transaction_date', $P_AUTH_DT );

						$order->add_order_note( sprintf( __( '가상계좌 무통장 입금이 완료되었습니다.  거래번호(TID) : %s, 상점거래번호(OID) : %s', 'pgall-for-woocommerce' ), $P_TID, $P_OID ) );
						$this->add_log( '[모바일] 모바일 가상계좌 입금통보 성공.' );
						$order->payment_complete( $P_TID );

						do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );

						$order->update_status( $this->settings['order_status_after_payment'] );
						echo 'OK';
						exit();
					} else { //주문상태가 이상한 경우
						$order->add_order_note( sprintf( __( '[모바일] 입금통보 내역이 수신되었으나, 주문 상태에 문제가 있습니다. 이미 완료된 주문이거나, 환불된 주문일 수 있습니다. 전송서버IP : %s, 거래번호(TID) : %s, 상점거래번호(OID) : %s, 입금은행코드 : %s, 입금은행명 : %s, 입금가상계좌번호 : %s, 입금액 : %s, 입금자명 : %s', 'pgall-for-woocommerce' ), $_SERVER['REMOTE_ADDR'], $P_TID, $P_OID, $P_FN_CD1, mb_convert_encoding( $P_FN_NM, "UTF-8", "EUC-KR" ), $p_vacct_no, number_format( $P_AMT ), mb_convert_encoding( $P_UNAME, "UTF-8", "EUC-KR" ) ) );
						$this->add_log( '[모바일] 모바일 가상계좌 입금통보 실패 : 주문상태 - ' . $order->get_status() . "\n" . print_r( $_REQUEST, true ) );
						echo 'OK';    //가맹점 관리자 사이트에서 재전송 가능하나 주문건 확인 필요
						exit();
					}
				} else {
					$this->add_log( '[모바일] 모바일 가상계좌 입금통보 실패 : 결제 결과 이상 -  ' . $P_STATUS . "\n" . print_r( $_REQUEST, true ) );
					echo "OK";

					return;
				}
			}
			public function validate_order_status( $order, $auto_cancel = false ) {
			}
			function vbank_refund_request() {
				$this->check_shop_order_capability();

				$order = $this->get_order();

				$vbank_lists = $this->get_vbank_list();
				$_REQUEST['refund_acc_num'] = str_replace( '-', '', $_REQUEST['refund_acc_num'] );
				pafw_update_meta_data( $order, '_pafw_vbank_refund_bank_code', $_REQUEST['refund_bank_code'] );
				pafw_update_meta_data( $order, '_pafw_vbank_refund_bank_name', $vbank_lists[ $_REQUEST['refund_bank_code'] ] );
				pafw_update_meta_data( $order, '_pafw_vbank_refund_acc_num', $_REQUEST['refund_acc_num'] );
				pafw_update_meta_data( $order, '_pafw_vbank_refund_acc_name', $_REQUEST['refund_acc_name'] );
				pafw_update_meta_data( $order, '_pafw_vbank_refund_reason', $_REQUEST['refund_reason'] );

				$this->add_log( '가상계좌 환불 처리 시작.' );

				$tid = $this->get_transaction_id( $order );

				if ( version_compare( PHP_VERSION, '7.1.0' ) < 0 ) {
					require_once( PAFW()->plugin_path() . "/lib/inicis/inipay50/INILib.php" );
				} else {
					require_once( PAFW()->plugin_path() . "/lib/inicis/inipay50_71/INILib.php" );
				}

				$inipay = new INIpay50();
				$inipay->SetField( "inipayhome", $this->settings['libfolder'] );       // 이니페이 홈디렉터리(상점수정 필요)
				$inipay->SetField( "type", "refund" );      // 고정 (절대 수정 불가)
				$inipay->SetField( "debug", "false" );        // 로그모드("true"로 설정하면 상세로그가 생성됨.)
				$inipay->SetField( "mid", $this->merchant_id );            // 상점아이디
				$inipay->SetField( "admin", "1111" );         //비대칭 사용키 키패스워드
				$inipay->SetField( "tid", $tid );            // 환불할 거래의 거래아이디
				$inipay->SetField( "cancelmsg", mb_convert_encoding( $_REQUEST['refund_reason'], "EUC-KR", "UTF-8" ) );            // 환불사유
				$inipay->SetField( "racctnum", $_REQUEST['refund_acc_num'] );
				$inipay->SetField( "rbankcode", $_REQUEST['refund_bank_code'] );
				$inipay->SetField( "racctname", mb_convert_encoding( $_REQUEST['refund_acc_name'], "EUC-KR", "UTF-8" ) );
				$inipay->startAction();

				if ( $inipay->getResult( 'ResultCode' ) == '00' ) {
					$order->update_status( 'refunded', __( '관리자의 요청으로 주문건의 가상계좌 환불처리가 완료되었습니다.', 'pgall-for-woocommerce' ) );
					pafw_update_meta_data( $order, '_pafw_vbank_refunded', 'yes' );
					pafw_update_meta_data( $order, '_pafw_order_cancelled', 'yes' );
					pafw_update_meta_data( $order, '_pafw_cancel_date', current_time( 'mysql' ) );
					$this->add_log( '가상계좌 환불처리 요청 성공. 주문번호 : %s', pafw_get_object_property( $order, 'id' ) );
					wp_send_json_success( __( '관리자의 요청으로 주문건의 가상계좌 환불처리가 완료되었습니다.', 'pgall-for-woocommerce' ) );
				} else {
					$order->add_order_note( sprintf( __( '가상계좌 환불처리가 실패하였습니다. 결과코드 : %s, 처리메시지 : %s', 'pgall-for-woocommerce' ), $inipay->getResult( 'ResultCode' ), mb_convert_encoding( $inipay->GetResult( 'ResultMsg' ), "UTF-8", "EUC-KR" ) ) );
					$this->add_log( "가상계좌 환불처리 요청 실패\n" . print_r( $inipay, true ) );
					throw new Exception( sprintf( __( '가상계좌 환불처리가 실패하였습니다. 결과코드 : %s, 처리메시지 : %s', 'pgall-for-woocommerce' ), $inipay->getResult( 'ResultCode' ), mb_convert_encoding( $inipay->GetResult( 'ResultMsg' ), "UTF-8", "EUC-KR" ) ) );
				}
			}
			function process_vbank_nofi( $posted ) {
				$TEMP_IP = getenv( "REMOTE_ADDR" );
				$PG_IP   = substr( $TEMP_IP, 0, 10 );

				$this->add_log( '가상계좌 입금통보 시작 : ' . $TEMP_IP );

				if ( $PG_IP == "203.238.37" || $PG_IP == "210.98.138" || $PG_IP == "39.115.212" )  //PG에서 보냈는지 IP로 체크
				{
					$msg_id      = $_POST['msg_id'];             //메세지 타입
					$no_tid      = $_POST['no_tid'];             //거래번호
					$no_oid      = $_POST['no_oid'];             //상점 주문번호
					$id_merchant = $_POST['id_merchant'];   //상점 아이디
					$cd_bank     = $_POST['cd_bank'];           //거래 발생 기관 코드
					$cd_deal     = $_POST['cd_deal'];           //취급 기관 코드
					$dt_trans    = $_POST['dt_trans'];         //거래 일자
					$tm_trans    = $_POST['tm_trans'];         //거래 시간
					$no_msgseq   = $_POST['no_msgseq'];       //전문 일련 번호
					$cd_joinorg  = $_POST['cd_joinorg'];     //제휴 기관 코드

					$dt_transbase = $_POST['dt_transbase']; //거래 기준 일자
					$no_transeq   = $_POST['no_transeq'];     //거래 일련 번호
					$type_msg     = $_POST['type_msg'];         //거래 구분 코드
					$cl_close     = $_POST['cl_close'];         //마감 구분코드
					$cl_kor       = $_POST['cl_kor'];             //한글 구분 코드
					$no_msgmanage = $_POST['no_msgmanage']; //전문 관리 번호
					$no_vacct     = $_POST['no_vacct'];         //가상계좌번호
					$amt_input    = $_POST['amt_input'];       //입금금액
					$amt_check    = $_POST['amt_check'];       //미결제 타점권 금액
					$nm_inputbank = mb_convert_encoding( $_POST['nm_inputbank'], "UTF-8", "CP949" ); //입금 금융기관명
					$nm_input     = mb_convert_encoding( $_POST['nm_input'], "UTF-8", "CP949" );         //입금 의뢰인
					$dt_inputstd  = $_POST['dt_inputstd'];   //입금 기준 일자
					$dt_calculstd = $_POST['dt_calculstd']; //정산 기준 일자
					$flg_close    = $_POST['flg_close'];       //마감 전화

					//가상계좌채번시 현금영수증 자동발급신청시에만 전달
					$dt_cshr      = $_POST['dt_cshr'];       //현금영수증 발급일자
					$tm_cshr      = $_POST['tm_cshr'];       //현금영수증 발급시간
					$no_cshr_appl = $_POST['no_cshr_appl'];  //현금영수증 발급번호
					$no_cshr_tid  = $_POST['no_cshr_tid'];   //현금영수증 발급TID

					//OID 에서 주문번호 확인
					$arr_oid    = explode( '_', $no_oid );
					$order_id   = $arr_oid[0];
					$order_date = $arr_oid[1];
					$order_time = $arr_oid[2];

					$order          = wc_get_order( $order_id );
					$txnid          = pafw_get_meta( $order, '_pafw_txnid' );
					$tid            = pafw_get_meta( $order, '_pafw_vacc_tid' );
					$vact_num       = pafw_get_meta( $order, '_pafw_vacc_num' );
					$vact_bank_code = pafw_get_meta( $order, '_pafw_vacc_bank_code' );
					$vact_bank_name = pafw_get_meta( $order, '_pafw_vacc_bank_name' );
					$vact_holder    = pafw_get_meta( $order, '_pafw_vacc_holder' );
					$vact_depositor = pafw_get_meta( $order, '_pafw_vacc_depositor' );
					$vact_date      = pafw_get_meta( $order, '_pafw_vacc_date' );

					if ( ! in_array( $order->get_status(), array ( 'completed', 'cancelled', 'refunded' ) ) ) {  //주문상태 확인
						if ( $txnid != $no_oid ) {    //거래번호(oid) 체크
							$this->add_log( "ERROR : FAIL_11, 거래번호 미일치\n" . print_r( $_REQUEST, true ) );
							echo 'FAIL_11';
							exit();
						}
						if ( $cd_bank != $vact_bank_code ) {    //입금은행 코드 체크
							$this->add_log( "ERROR : FAIL_12, 입금은행 코드 미일치\n" . print_r( $_REQUEST, true ) );
							echo 'FAIL_12';
							exit();
						}
						if ( $no_vacct != $vact_num ) {    //입금계좌번호 체크
							$this->add_log( "ERROR : FAIL_13, 입금계좌번호 미일치\n" . print_r( $_REQUEST, true ) );
							echo 'FAIL_13';
							exit();
						}
						if ( (int) $amt_input != (int) $order->get_total() ) {    //입금액 체크
							$this->add_log( "ERROR : FAIL_14, 입금액 미일치\n" . print_r( $_REQUEST, true ) );
							echo 'FAIL_14';
							exit();
						}

						pafw_update_meta_data( $order, '_pafw_vbank_noti_received', 'yes' );
						pafw_update_meta_data( $order, '_pafw_vbank_noti_transaction_date', $dt_trans . $tm_trans );
						pafw_update_meta_data( $order, '_pafw_vbank_noti_deposit_bank', $nm_inputbank );
						pafw_update_meta_data( $order, '_pafw_vbank_noti_depositor', $nm_input );

						$order->add_order_note( sprintf( __( '가상계좌 무통장 입금이 완료되었습니다.  거래번호(TID) : %s, 상점거래번호(OID) : %s', 'pgall-for-woocommerce' ), $no_tid, $no_oid ) );
						$this->add_log( sprintf( __( '가상계좌 무통장 입금이 완료되었습니다.  거래번호(TID) : %s, 상점거래번호(OID) : %s', 'pgall-for-woocommerce' ), $no_tid, $no_oid ) );
						$order->payment_complete( $no_tid );

						do_action( 'pafw_payment_action', 'completed', $order->get_total(), $order, $this );

						$order->update_status( $this->settings['order_status_after_payment'] );

						//WC3.0 관련 가상계좌 입금통보시 결제 완료 시간 갱신 처리
						if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
							$order->set_date_paid( current_time( 'timestamp', true ) );
							$order->save();
						}

						echo 'OK';
						exit();
					} else { //주문상태가 이상한 경우
						$order->add_order_note( sprintf( __( '입금통보 내역이 수신되었으나, 주문 상태에 문제가 있습니다. 이미 완료된 주문이거나, 환불된 주문일 수 있습니다. 거래번호(TID) : %s, 상점거래번호(OID) : %s', 'pgall-for-woocommerce' ), $no_tid, $no_oid ) );
						$this->add_log( sprintf( __( '입금통보 내역이 수신되었으나, 주문 상태에 문제가 있습니다. 이미 완료된 주문이거나, 환불된 주문일 수 있습니다. 거래번호(TID) : %s, 상점거래번호(OID) : %s', 'pgall-for-woocommerce' ), $no_tid, $no_oid ) );
						$this->add_log( print_r( $_REQUEST, true ) );
						echo 'OK';    //가맹점 관리자 사이트에서 재전송 가능하나 주문건 확인 필요
						exit();
					}
				}
			}
		}
	}

} // class_exists function end