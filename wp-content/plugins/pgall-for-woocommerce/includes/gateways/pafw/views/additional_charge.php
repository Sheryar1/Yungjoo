<?php

$disabled = in_array( $order->get_status(), array ( 'pending', 'failed', 'cancelled', 'refunded' ) );

$history  = pafw_get_meta( $order, '_pafw_additional_charge_history' );
$idx      = 1;

?>

<div class="pafw_payment_info">
    <h4>과금 요청 금액</h4>
    <p>
        <input type="text" id="subscription_additional_charge_amount" placeholder="금액을 입력하세요." value="">
    </p>
</div>

<div class="pafw_button_wrapper">
    <input type="button" class="button pafw_action_button tips" id="pafw-subscription-additional-charge" value="<?php _e( '과금요청', 'pgall-for-woocommerce' ); ?>" data-tip="발급된 정기결제 배치키를 이용해서 추가 과금을 요청합니다." <?php echo $disabled ? 'disabled="disabled"' : ''; ?>}>
</div>

<?php if ( ! empty( $history ) ) : ?>
    <div class="pafw_payment_info">
        <h4>추가 과금 내역</h4>
		<?php foreach ( $history as $tid => $item ) : ?>
            <p>[#<?php echo $idx; ?>] 처리시간 : <?php echo date( 'Y-m-d H:i:s', strtotime( $item['auth_date'] ) ); ?></p>
            <p>[#<?php echo $idx; ?>] 과금금액 : <?php echo number_format( $item['charged_amount'] ); ?></p>
            <p>
                [#<?php echo $idx ++; ?>] 처리상태 : <?php echo $item['status']; ?>
				<?php if ( 'PAYED' == $item['status'] ) : ?>
                    <input type="button" style="width: auto; margin-left: 10px;" class="button tips pafw-subscription-cancel-additional-charge" data-tid="<?php echo $tid; ?>" data-amount="<?php echo $item['charged_amount']; ?>" value="<?php _e( '취소요청', 'pgall-for-woocommerce' ); ?>" data-tip="추가 과금을 취소합니다.">
				<?php endif; ?>
            </p>
		<?php endforeach; ?>
    </div>
<?php endif; ?>
