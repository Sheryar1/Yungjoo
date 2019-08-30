<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	if ( ! class_exists( 'WC_Gateway_Nicepay_Escrow_Bank' ) ) {

		class WC_Gateway_Nicepay_Escrow_Bank extends WC_Gateway_Nicepay {
			public function __construct() {
				$this->id = 'nicepay_escrow_bank';

				parent::__construct();

				$this->settings['paymethod'] = 'BANK';

				if ( empty( $this->settings['title'] ) ) {
					$this->title       = __( '실시간계좌이체(에스크로)', 'pgall-for-woocommerce' );
					$this->description = __( '에스크로 방식으로 계좌에서 바로 결제하는 에스크로 실시간 계좌이체 입니다.', 'pgall-for-woocommerce' );
				} else {
					$this->title       = $this->settings['title'];
					$this->description = $this->settings['description'];
				}

				$this->success_code = '4000';
				$this->supports[] = 'pafw-escrow';
			}

			public function process_standard( $order, $responseDTO ) {
				$transaction_id = $responseDTO->getParameter( "TID" );
				$txnid          = $responseDTO->getParameter( "Moid" );
				$bank_code      = $responseDTO->getParameter( "BankCode" );       // 은행코드
				$bank_name      = $responseDTO->getParameterUTF( "BankName" );    // 은행명
				$rcpt_type      = $responseDTO->getParameter( "RcptType" );       // 현금 영수증 타입 (0:발행되지않음,1:소득공제,2:지출증빙)

				pafw_update_meta_data( $order, '_pafw_bank_code', $bank_code );
				pafw_update_meta_data( $order, '_pafw_bank_name', $bank_name );
				pafw_update_meta_data( $order, '_pafw_cash_receipts', '0' != $rcpt_type ? '발행' : '미발행' );

				$this->add_payment_log( $order, '[ 결제 승인 완료 ]', array (
					'거래번호' => $transaction_id
				) );
			}

			function escrow_register_delivery_info() {
				$this->check_shop_order_capability();

				$order          = $this->get_order();
				$transaction_id = $this->get_transaction_id( $order );

				require_once PAFW()->plugin_path() . '/lib/nicepay/NicepayLite.php';

				$nicepay                = new NicepayLite();
				$nicepay->m_NicepayHome = $this->get_nicepay_log_path();

				$ReqType                 = '03';
				$nicepay->m_MID          = $this->settings['merchant_id'];
				$nicepay->m_TID          = $transaction_id;
				$nicepay->m_DeliveryCoNm = mb_convert_encoding( $this->settings['delivery_company_name'], "CP949", "UTF-8" );
				$nicepay->m_InvoiceNum   = $_POST['tracking_number'];
				$nicepay->m_BuyerAddr    = mb_convert_encoding( $this->settings['delivery_sender_addr'], "CP949", "UTF-8" );
				$nicepay->m_RegisterName = mb_convert_encoding( $this->settings['delivery_register_name'], "CP949", "UTF-8" );
				$nicepay->m_BuyerAuthNum = '';  //배송등록시 미사용
				$nicepay->m_PayMethod    = 'ESCROW';
				$nicepay->m_ReqType      = $ReqType; //03:배송등록
				$nicepay->m_ConfirmMail  = '1';  //1:발송,0:미발송
				$nicepay->m_ActionType   = "PYO";
				$nicepay->m_LicenseKey   = $this->settings['merchant_key'];

				$nicepay->startAction();

				$resultCode = $nicepay->m_ResultData["ResultCode"];    // 결과 코드
				$resultMsg  = $nicepay->m_ResultData["ResultMsg"];    // 결과 메시지

				$escrowSuccess = false;        // 에스크로 처리 성공 여부
				if ( $ReqType == "01" ) {                //	구매확인
					if ( $resultCode == "D000" ) {
						$escrowSuccess = true;
					}    // 결과코드 (정상 :D000 , 그 외 에러)
				} else if ( $ReqType == "02" ) {            //구매거절
					if ( $resultCode == "E000" ) {
						$escrowSuccess = true;
					}    // 결과코드 (정상 :E000 , 그 외 에러)
				} else if ( $ReqType == "03" ) {            //배송등록
					if ( $resultCode == "C000" ) {
						$escrowSuccess = true;
					}    // 결과코드 (정상 :C000 , 그 외 에러)
				}

				//배송 등록 성공/실패에 따른 분기 처리
				if ( $escrowSuccess == true ) {
					pafw_update_meta_data( $order, '_pafw_escrow_tracking_number', $_POST['tracking_number'] );
					pafw_update_meta_data( $order, '_pafw_escrow_register_delivery_info', 'yes' );
					pafw_update_meta_data( $order, '_pafw_escrow_register_delivery_time', current_time( 'mysql' ) );

					$order->add_order_note( __( '판매자님께서 고객님의 에스크로 결제 주문을 배송 등록 또는 수정 처리하였습니다.', 'pgall-for-woocommerce' ), true );
					$order->update_status( $this->order_status_after_enter_shipping_number );
				} else {
					throw new Exception( sprintf( __( '배송등록중 오류가 발생했습니다. [%s] %s', 'pgall-for-woocommerce' ), $resultCode, mb_convert_encoding( $resultMsg, "UTF-8", "EUC-KR" ) ) );
				}

				wp_send_json_success( __( '배송등록이 처리되었습니다.', 'pgall-for-woocommerce' ) );
			}
			function nicepay_escrow_mypage_accept_request( $order_id ) {

				$order = wc_get_order( $order_id );

				$tmp_setting           = get_option( 'nicepay_pg_nicepay_escrowbank_possible_escrow_confirm_status_for_customer', 'shipped' );
				$confirm_mypage_status = explode( ',', $tmp_setting );
				if ( ! in_array( $order->get_status(), $confirm_mypage_status ) ) {
					return;
				}

				$ReqType = get_post_meta( $order_id, '_nicepay_paymethod_escrow_delivery_confirm', true );

				if ( ! empty( $ReqType ) ) {
					$ReqTypeText = '';
					if ( $ReqType == '01' ) {
						$ReqTypeText = '<span id="nicepay_confirm_result" style="color:blue;font-weight: bold;">구매확인</span>';
					}
					if ( $ReqType == '02' ) {
						$ReqTypeText = '<span id="nicepay_confirm_result"  style="color:red;font-weight: bold;">구매거절</span>';
					}
					?>
                    <h2><?php _e( '구매 확인/거절', 'pgall-for-woocommerce' ); ?></h2>
                    <p class="order-info"><?php _e( '구매 확인/거절 처리가 완료되었습니다. 구매 확인/거절 관련해서 문의사항이 있는 경우 사이트 관리자에게 문의해주세요.', 'pgall-for-woocommerce' ); ?></p>
                    <p class="order-info">최종 구매 확인/거절 결과 : <?php echo $ReqTypeText; ?></p>
					<?php
					return;
				}

				//나이스페이 결제 모듈 체크
				if ( ! file_exists( PAFW()->plugin_path() . "/lib/mshop-nicepay/NicepayLite.php" ) ) {
					PAFW()->nicepay_print_log( __( '에러 : NicepayLite.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ), 'nicepay' );
					wc_add_notice( __( '에러 : NicepayLite.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ), 'error' );
					die( '<span style="color:red;font-weight:bold;">' . __( '에러 : NicepayLite.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ) . '</span>' );
				}

				require_once PAFW()->plugin_path() . '/lib/mshop-nicepay/NicepayLite.php';
				$nicepay = new NicepayLite();
				$nicepay->requestProcess();

				?>
                <script type="text/javascript">
					//배송정보 등록버튼 클릭 처리
					function NicepayDeliveryConfirm () {
						var confirm_type = jQuery( '#confirm_type:checked' ).val();
						var BuyerAuthNum = jQuery( '#BuyerAuthNum' ).val();

						var confirm_type_text = '구매 확인';

						if ( confirm_type == '01' ) {
							confirm_type_text = '구매 확인';
						}
						if ( confirm_type == '02' ) {
							confirm_type_text = '구매 거절';
						}

						if ( confirm_type != '' && BuyerAuthNum != '' ) {
							if ( confirm( '***************** 주의 *****************\n\n[' + confirm_type_text + '] 절차를 진행하시겠습니까?\n\n처리 이후에는 취소할 수 없습니다.' ) ) {
								jQuery( '[name=\'button_confirm_request\']' ).attr( 'disabled', 'true' );
								jQuery( '[name=\'button_confirm_request\']' ).val( '처리중' );
								jQuery.ajax( {
									type: 'POST',
									dataType: 'text',
									url: '<?php echo home_url() . '/wc-api/WC_Gateway_Nicepay_Escrowbank?type=delivery_confirm'; ?>',
									data: {
										post_id: '<?php echo $order_id; ?>',
										UserIP: '<?php echo $nicepay->m_UserIp; ?>',
										MallIP: '<?php echo $nicepay->m_MerchantServerIp; ?>',
										confirm_type: confirm_type,
										BuyerAuthNum: BuyerAuthNum,
										nonce: '<?php echo wp_create_nonce( 'nicepay_confirm_request' ); ?>',
									},
									success: function ( data, textStatus, jqXHR ) {
										if ( data.match( 'success' ) ) {
											alert( '[' + confirm_type_text + '] 처리가 완료되었습니다.\n\n고객님께 물품 수령후에 에스크로 구매확인 및 거절 의사를 표시 요청을 하셔야 합니다.' );
											location.reload();
										} else {
											alert( '관리자에게 문의하여주세요.\n\n에러 메시지 : \n' + data );
											jQuery( '[name=\'button_confirm_request\']' ).removeAttr( 'disabled' );
											jQuery( '[name=\'button_confirm_request\']' ).val( '확인' );
											location.reload();
										}
									}
								} );
							} else {
								return;
							}
						} else {
							alert( '- 구매자 식별번호(결제시 사용한 휴대폰 또는 사업자번호)를 입력하셨는지 확인해주세요.\n- 구매확인 및 거절 선택을 하셨는지 확인해주세요.' );
							return;
						}
					}
                </script>
				<?php

				echo '
				<h2>' . __( '구매 확인/거절', 'pgall-for-woocommerce' ) . '</h2>
				<p class="order-info">' . __( '구매하신 상품을 배송받으셨다면 상품을 확인한 후, 아래 버튼을 클릭하여 구매확인 및 구매거절 처리를 진행할 수 있습니다.', 'pgall-for-woocommerce' ) . '</p>
				<p><form name="accept_request" method="POST" action="">
                <input type="hidden" name="accept_order" id="accept_order" value="' . pafw_get_object_property( $order, 'id' ) . '"/>
                <p>결제시 사용한 휴대폰 또는 사업자번호(-제외)<input type="text" name="BuyerAuthNum" id="BuyerAuthNum" value="" placeholder="결제시 사용한 휴대폰 또는 사업자번호" maxlength=30/></p>
				<input type="radio" id="confirm_type" name="confirm_type" value="01"> 구매확인 <input type="radio" id="confirm_type" name="confirm_type" value="02"> 구매거절
				</p>
				';
				echo '<p><input type="button" class="button" id="button_confirm_request" name="button_confirm_request" value="' . __( '확인', 'pgall-for-woocommerce' ) . '" onClick="javascript:NicepayDeliveryConfirm();"/></p>';
			}
			function nicepay_escrow_delivery_confirm() {

				//nonce 값 체크하여 정상 요청인지 확인처리
				$nonce = $_REQUEST['nonce'];
				if ( ! wp_verify_nonce( $nonce, 'nicepay_confirm_request' ) ) {
					wp_send_json_error( __( '잘못된 요청이 접수되었습니다. 사이트를 새로고침 하신 후 다시 시도해주세요.', 'pgall-for-woocommerce' ) );
				}

				if ( ! file_exists( PAFW()->plugin_path() . "/lib/mshop-nicepay/NicepayLite.php" ) ) {
					$this->nicepay_print_log( __( '에러 : NicepayLite.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ), 'nicepay' );
					wc_add_notice( __( '에러 : NicepayLite.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ), 'error' );
					die( '<span style="color:red;font-weight:bold;">' . __( '에러 : NicepayLite.php 파일이 없습니다. 사이트 관리자에게 문의하여 주십시오.', 'pgall-for-woocommerce' ) . '</span>' );
				}

				require_once PAFW()->plugin_path() . '/lib/mshop-nicepay/NicepayLite.php';

				$nicepay                = new NicepayLite();
				$nicepay->m_NicepayHome = $this->get_nicepay_log_path();

				$order   = wc_get_order( $_POST['post_id'] );
				$ReqType = trim( $_POST['confirm_type'] );

				$nicepay->m_MID          = $this->settings['merchant_id'];
				$nicepay->m_TID          = $order->get_transaction_id();
				$nicepay->m_BuyerAuthNum = trim( $_POST['BuyerAuthNum'] );    //구매식별번호 (주민번호)
				$nicepay->m_PayMethod    = 'ESCROW';
				$nicepay->m_ReqType      = $_POST['confirm_type'];  //배송등록 타입 01:구매확인, 02:구매거절
				$nicepay->m_ActionType   = "PYO";
				$nicepay->m_LicenseKey   = $this->settings['merchant_key'];

				$nicepay->startAction();

				$resultCode = $nicepay->m_ResultData["ResultCode"];    // 결과 코드
				$resultMsg  = $nicepay->m_ResultData["ResultMsg"];    // 결과 메시지

				$escrowSuccess = false;        // 에스크로 처리 성공 여부
				$ReqTypeText   = '';
				if ( $ReqType == "01" ) {                //	구매확인
					$ReqTypeText = '구매확인';
					if ( $resultCode == "D000" ) {
						$escrowSuccess = true;
					}    // 결과코드 (정상 :D000 , 그 외 에러)
				} else if ( $ReqType == "02" ) {
					$ReqTypeText = '구매거절';//구매거절
					if ( $resultCode == "E000" ) {
						$escrowSuccess = true;
					}    // 결과코드 (정상 :E000 , 그 외 에러)
				} else if ( $ReqType == "03" ) {            //배송등록
					$ReqTypeText = '배송등록';
					if ( $resultCode == "C000" ) {
						$escrowSuccess = true;
					}    // 결과코드 (정상 :C000 , 그 외 에러)
				}

				//구매 확인/거절 처리에 따른 분기 처리
				if ( $escrowSuccess == true ) {
					$order->add_order_note( sprintf( __( '고객님이 에스크로 결제 주문을 %s 처리하였습니다. 가맹점 관리자에서 확인 후 이용해주세요.<br><hr>결과코드 : %s<br>구매확정구분 : %s', 'pgall-for-woocommerce' ), $ReqTypeText, $resultCode, $_POST['confirm_type'] ) );

					if ( $ReqType == '01' ) {    //구매확인
						$order->update_status( 'completed' );     //주문처리완료
					}

					if ( $ReqType == '02' ) {    //구매거절
						$order->update_status( 'cancel-request' );    //주문취소요청
					}

					//구매확인/거절된 경우 표기 처리
					update_post_meta( $_POST['post_id'], "_nicepay_paymethod_escrow_delivery_confirm", $ReqType );  //에스크로 배송 정보 최초 추가 확인값 추가
					echo "success";
					die();
				} else {
					$order->add_order_note( sprintf( __( '고객님이 에스크로 결제 주문을 %s 처리를 시도하였으나 실패하였습니다. 다시 한번 확인해주세요.<br><hr>결과코드 : %s<br>구매확정구분 : %s', 'pgall-for-woocommerce' ), $ReqTypeText, $resultCode, $_POST['confirm_type'] ) );
					echo 'Error [' . $resultCode . '] ' . mb_convert_encoding( $resultMsg, "UTF-8", "CP949" );
					die();
				}
			}
		}

	}

} // class_exists function end