<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Settings_Kcp' ) ) {
	abstract class PAFW_Settings_Kcp extends PAFW_Settings {

		// 테스트 환경용 파라미터
		static $sandbox = array (
			'site_cd'        => 'T0000',
			'site_key'       => '3grptw1.zW0GSo4PQdaGvsF__',
			'gw_url'         => 'testpaygw.kcp.co.kr',
			'log_level'      => '3',
			'js_url'         => 'https://testpay.kcp.co.kr/plugin/payplus_web.jsp',
			'wsdl'           => 'KCPPaymentService.wsdl',
			'bills_url'      => 'https://testadmin8.kcp.co.kr/assist/bill.BillActionNew.do?',
			'cash_bills_url' => 'https://testadmin8.kcp.co.kr/Modules/Service/Cash/Cash_Bill_Common_View.jsp?'
		);

		static $mobile_pay_method_desc = array (
			'card' => '신용카드',
			'acnt' => '계좌이체',
			'vcnt' => '가상계좌',
			'mobx' => '휴대폰'
		);

		static $pc_pay_method_desc = array (
			'100000000000' => '신용카드',
			'010000000000' => '계좌이체',
			'001000000000' => '가상계좌',
			'000010000000' => '휴대폰'
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

		public function __construct() {
			$this->master_id = 'kcp';

			$this->prefix = '';

			parent::__construct();
		}
		function get_basic_setting_fields() {
			$instance = pafw_get_settings( 'kcp_basic' );

			return $instance->get_setting_fields();
		}
		function get_advanced_setting_fields() {
			$instance = pafw_get_settings( 'kcp_advanced' );

			return $instance->get_setting_fields();
		}
	}
}
