<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
do_action( 'woocommerce_email_order_details', $return_order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $return_order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $return_order, $sent_to_admin, $plain_text, $email );
?>
    <p><?php esc_html_e( 'Over to you.', 'woocommerce' ); ?></p>
<?php
do_action( 'woocommerce_email_footer', $email );
