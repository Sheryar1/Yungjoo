<?php

if( !is_wp_error( $can_repay ) ){
	$title = __( '부분취소', 'pgall-for-woocommerce' ) . ' (누적 : ' . $repay_cnt . '회)';
}else {
	$title = $can_repay->get_error_message();
}
?>
<div class="pafw_payment_info">
	<h4><?php _e( '부분취소 내역', 'pgall-for-woocommerce' ); ?></h4>
	<p><?php printf( '결제금액 : %s', wc_price( $order->get_total() ) ); ?></p>
	<p><?php printf( '기 취소금액 : %s', wc_price( $order->get_total_refunded() ) ); ?></p>
	<p><?php printf( '취소 가능금액 : %s', wc_price( $order->get_total() - $order->get_total_refunded() ) ); ?></p>
</div>

<div class="pafw_button_wrapper">
	<input type="text" class="repay_price pafw_action_button" name="repay_price" id="repay_price" placeholder="0" value="" style="width:100%;text-align: right;ime-mode:disabled;" <?php echo is_wp_error( $can_repay ) ? 'disabled' : ''; ?>>
	<input type="button" class="button pafw_action_button" id="pafw-repay-request" name="pafw-repay-request" value="<?php echo $title; ?>" <?php echo is_wp_error( $can_repay ) ? 'disabled' : ''; ?>>
</div>
