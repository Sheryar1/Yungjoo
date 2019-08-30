<?php


//소스에 URL로 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Exception' ) ) {

	class PAFW_Exception extends Exception{
		protected $error_code = '';

		public function __construct( $message = "", $code = 0, $error_code = '', Throwable $previous = null ) {
			parent::__construct( $message, $code, $previous );

			$this->error_code = $error_code;
		}

		public function getErrorCode() {
			return $this->error_code;
		}
	}

}