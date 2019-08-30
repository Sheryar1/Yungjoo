<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Emails' ) ) {

	class PAFW_Emails {

		public static function init() {
			add_filter( 'woocommerce_email_actions', array( __CLASS__, 'woocommerce_email_actions' ) );
			add_filter( 'woocommerce_email_classes', array( __CLASS__, 'woocommerce_email_classes' ) );
		}
		static function woocommerce_email_actions( $email_actions ) {
			return array_merge( $email_actions, array(
				'woocommerce_order_status_exchange-request',
				'woocommerce_order_status_return-request'
			) );
		}
		static function woocommerce_email_classes( $emails ) {

			$emails['PAFW_Email_Exchange_Request']  = include( 'emails/class-pafw-email-exchange-request.php' );
			$emails['PAFW_Email_Return_Request'] = include( 'emails/class-pafw-email-return-request.php' );

			return $emails;
		}
	}

	PAFW_Emails::init();
}