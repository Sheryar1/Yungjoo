<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$order = wc_get_order( $order_id );

/** @var PAFW_Payment_Gateway $payment_gateway */
$payment_gateway = pafw_get_payment_gateway_from_order( $order );

if ( empty( $payment_gateway ) || ( 'bacs' != $payment_gateway->id && ! $payment_gateway->supports( 'pafw-vbank' ) ) ) {
	return;
}

?>
<div class="field">
    <label>환불 계좌 정보를 입력하세요.</label>
</div>
<div class="field pafw-ex-reason">
    <input type="text" name="refund_bank_account" class="input-text" placeholder="환불받으실 은행명, 계좌번호, 예금주명을 입력해주세요."></input>
</div>