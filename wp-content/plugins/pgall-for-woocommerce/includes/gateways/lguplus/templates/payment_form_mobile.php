<?php

$LGD_WINDOW_TYPE = 'submit';
$LGD_CUSTOM_SKIN = "SMART_XPAY2";
$LGD_CUSTOM_PROCESSTYPE     = "TWOTR";                                       //수정불가
$LGD_CUSTOM_USABLEPAY       = $this->paymethod;        	     //디폴트 결제수단 (해당 필드를 보내지 않으면 결제수단 선택 UI 가 노출됩니다.)
$LGD_WINDOW_VER		        = "2.5";										 //결제창 버젼정보
$LGD_CUSTOM_SWITCHINGTYPE   = 'SUBMIT';
$LGD_OSTYPE_CHECK           = "P";
$LGD_KVPMISPWAPURL		= "";
$LGD_KVPMISPCANCELURL   = "";

$LGD_MPILOTTEAPPCARDWAPURL = ""; //iOS 연동시 필수
$LGD_MTRANSFERWAPURL 		= "";
$LGD_MTRANSFERCANCELURL 	= "";
//$LGD_RETURNURL 	= "http://mall1.codemshop.com/wc-api/WC_Gateway_Lguplus_Card?type=pc_payment";

$payReqMap['CST_PLATFORM']             = $CST_PLATFORM;                // 테스트, 서비스 구분
$payReqMap['LGD_WINDOW_TYPE']          = $LGD_WINDOW_TYPE;            // 수정불가
$payReqMap['CST_MID']                  = $CST_MID;                    // 상점아이디
$payReqMap['LGD_MID']                  = $LGD_MID;                    // 상점아이디
$payReqMap['LGD_OID']                  = $LGD_OID;                    // 주문번호
$payReqMap['LGD_BUYER']                = $LGD_BUYER;
$payReqMap['LGD_PRODUCTINFO']          = $LGD_PRODUCTINFO;            // 상품정보
$payReqMap['LGD_AMOUNT']               = $LGD_AMOUNT;                    // 결제금액
$payReqMap['LGD_BUYEREMAIL']           = $LGD_BUYEREMAIL;                // 구매자 이메일
$payReqMap['LGD_CUSTOM_SKIN']          = $LGD_CUSTOM_SKIN;            // 결제창 SKIN
$payReqMap['LGD_CUSTOM_PROCESSTYPE']   = $LGD_CUSTOM_PROCESSTYPE;        // 트랜잭션 처리방식
$payReqMap['LGD_TIMESTAMP']            = $LGD_TIMESTAMP;                // 타임스탬프
$payReqMap['LGD_HASHDATA']             = $LGD_HASHDATA;                // MD5 해쉬암호값
$payReqMap['LGD_RETURNURL']            = $LGD_RETURNURL;                // 응답수신페이지
$payReqMap['LGD_VERSION']              = "PHP_Non-ActiveX_SmartXPay";    // 버전정보 (삭제하지 마세요)
$payReqMap['LGD_CUSTOM_FIRSTPAY']      = $LGD_CUSTOM_USABLEPAY;    // 디폴트 결제수단
$payReqMap['LGD_CUSTOM_SWITCHINGTYPE'] = $LGD_CUSTOM_SWITCHINGTYPE;// 신용카드 카드사 인증 페이지 연동 방식
$payReqMap['LGD_PCVIEWYN']			 = '';				// 휴대폰번호 입력 화면 사용 여부(유심칩이 없는 단말기에서 입력-->유심칩이 있는 휴대폰에서 실제 결제)
$payReqMap['LGD_ENCODING']           = 'UTF-8';
$payReqMap['LGD_ENCODING_RETURNURL'] = 'UTF-8';



// 가상계좌(무통장) 결제연동을 하시는 경우  할당/입금 결과를 통보받기 위해 반드시 LGD_CASNOTEURL 정보를 LG 유플러스에 전송해야 합니다 .
$payReqMap['LGD_CASNOTEURL'] = $LGD_CASNOTEURL;               // 가상계좌 NOTEURL

//iOS 연동시 필수
$payReqMap['LGD_MPILOTTEAPPCARDWAPURL'] = $LGD_MPILOTTEAPPCARDWAPURL;
$payReqMap['LGD_KVPMISPWAPURL']		 	= $LGD_KVPMISPWAPURL;
$payReqMap['LGD_KVPMISPCANCELURL']  	= $LGD_KVPMISPCANCELURL;
$payReqMap['LGD_MTRANSFERWAPURL']		= $LGD_MTRANSFERWAPURL;
$payReqMap['LGD_MTRANSFERCANCELURL']  	= $LGD_MTRANSFERCANCELURL;
$payReqMap['LGD_KVPMISPAUTOAPPYN']	= "A";		// 신용카드 결제
$payReqMap['LGD_MTRANSFERAUTOAPPYN']= "A";		// 계좌이체 결제

// 가상계좌(무통장) 결제연동을 하시는 경우  할당/입금 결과를 통보받기 위해 반드시 LGD_CASNOTEURL 정보를 LG 유플러스에 전송해야 합니다 .
$payReqMap['LGD_CASNOTEURL'] = $LGD_CASNOTEURL;               // 가상계좌 NOTEURL

$payReqMap['LGD_ESCROW_USEYN']         = $this->supports( 'pafw-escrow' ) ? 'Y' : 'N';

// 가상계좌(무통장) 결제연동을 하시는 경우  할당/입금 결과를 통보받기 위해 반드시 LGD_CASNOTEURL 정보를 LG 유플러스에 전송해야 합니다 .
if ( $this->is_vbank() ) {
	$date_limit = pafw_get( $this->settings, 'account_date_limit', 3 );
	$close_date = date( 'Ymd', strtotime( current_time( 'mysql' ) . " +" . $date_limit . " days" ) ) . '235959';
	pafw_update_meta_data( $order, '_pafw_vacc_date', $close_date );

	$payReqMap['LGD_CASNOTEURL'] = $this->get_api_url( 'vbank_noti' );
	$payReqMap['LGD_CLOSEDATE']  = $close_date;
}

//Return URL에서 인증 결과 수신 시 셋팅될 파라미터 입니다.*/
$payReqMap['LGD_RESPCODE']           = "";
$payReqMap['LGD_RESPMSG']            = "";
$payReqMap['LGD_PAYKEY']             = "";
$_SESSION['PAYREQ_MAP'] = $payReqMap;
?>

<form method="post" name="LGD_PAYINFO" id="LGD_PAYINFO" action="">
	<?php
	foreach ( $payReqMap as $key => $value ) {
		echo "<input type='hidden' name='$key' id='$key' value='$value'>";
	}
	?>
</form>