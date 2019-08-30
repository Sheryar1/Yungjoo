<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div class="pafw-instant-payment-wrapper need-login">
    <a href="<?php echo wp_login_url( $_SERVER['REQUEST_URI']); ?>" class="button button-primary"><?php _e('로그인  후 결제를 진행 해 주세요.','pgall-for-woocommerce'); ?></a>
</div>
