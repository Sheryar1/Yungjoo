<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PAFW_Email_Exchange_Reqeust' ) ) :


	class PAFW_Email_Exchange_Reqeust extends WC_Email {
		public $order;
		public function __construct() {
			$this->id = 'exchange_request';

			$this->title         = __( '교환 요청', 'pgall-for-woocommerce' );
			$this->description   = __( '고객이 교환 요청 시 관리자에게 전달되는 이메일 입니다.', 'pgall-for-woocommerce' );
			$this->template_html = 'emails/admin-exchange-request.php';
			$this->template_base = PAFW()->template_path();
			$this->placeholders  = array (
				'{site_title}'   => $this->get_blogname(),
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Trigger
			add_action( 'pafw-exchange-request-notification', array ( $this, 'trigger' ), 10, 2 );

			// Call parent constructor
			parent::__construct();

			// Other settings.
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}
		public function get_default_subject() {
			return __( '[{site_title}] #{order_number} 주문에 대한 교환 요청이 접수되었습니다.', 'pgall-for-woocommerce' );
		}
		public function get_default_heading() {
			return __( '교환 요청이 접수되었습니다.', 'pgall-for-woocommerce' );
		}
		public function trigger( $order_id, $order = false ) {
			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}
		public function get_content_html() {
			return wc_get_template_html( $this->template_html, array (
				'exchange_order' => $this->object,
				'order'          => wc_get_order( $this->object->get_parent_id() ),
				'email_heading'  => $this->get_heading(),
				'sent_to_admin'  => false,
				'plain_text'     => false,
				'email'          => $this,
			), '', PAFW()->template_path() );
		}
		public function init_form_fields() {
			$this->form_fields = array (
				'enabled'    => array (
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'woocommerce' ),
					'default' => 'yes',
				),
				'recipient'  => array (
					'title'       => __( 'Recipient(s)', 'woocommerce' ),
					'type'        => 'text',
					'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'woocommerce' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
				),
				'subject'    => array (
					'title'       => __( 'Subject', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>{site_title}, {order_date}, {order_number}</code>' ),
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'    => array (
					'title'       => __( 'Email heading', 'woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>{site_title}, {order_date}, {order_number}</code>' ),
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'email_type' => array (
					'title'       => __( 'Email type', 'woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				)
			);
		}
	}

endif;

return new PAFW_Email_Exchange_Reqeust();
