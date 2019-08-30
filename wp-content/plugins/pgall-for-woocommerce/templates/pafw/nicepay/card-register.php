<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

wp_register_style( 'pafw-nicepay-form-fields', PAFW()->plugin_url() . '/assets/gateways/nicepay/css/form-fields.css' );
wp_enqueue_style( 'pafw-nicepay-form-fields' );

?>
<h1><?php _e( '결제 카드 관리', 'pgall-for-woocommerce' ); ?></h1>
<ul class="cks-notice">
    <li><?php _e( '카드 등록은 1개만 가능합니다.', 'pgall-for-woocommerce' ); ?></li>
</ul>

<form class="cks-card-form" action="POST">
    <div class="pafw_card_form">
        <?php
        include( PAFW()->plugin_path() . '/includes/gateways/' . $payment_gateway->master_id . '/templates/form-payment-fields.php' );
        ?>
    </div>

    <input type="button" name="register-card" class="button button-primary pafw_register_card cks-btn" value="등록하기">
</form>
