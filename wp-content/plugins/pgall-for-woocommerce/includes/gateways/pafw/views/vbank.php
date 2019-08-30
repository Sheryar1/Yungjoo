<?php

$vbank_refund_bank_code = pafw_get_meta( $order, '_pafw_vbank_refund_bank_code' );
$vbank_refund_acc_num   = pafw_get_meta( $order, '_pafw_vbank_refund_acc_num' );
$vbank_refund_acc_name  = pafw_get_meta( $order, '_pafw_vbank_refund_acc_name' );
$vbank_refund_reason    = pafw_get_meta( $order, '_pafw_vbank_refund_reason' );
$vbank_refunded         = pafw_get_meta( $order, '_pafw_vbank_refunded' );
$disabled = ( 'yes' != $vbank_refunded && $refundable ) ? '' : 'disabled';

$vbank_lists = is_callable( array ( $this, 'get_vbank_list' ) ) ? $this->get_vbank_list() : array ();

?>

<script>
	jQuery( document ).ready( function ( $ ) {
		$( '#vbank_bankcode' ).select2();
	} );

</script>
<div class="pafw_payment_info">
    <h4><?php _e( '가상계좌 환불처리는 전액환불만 가능하며 PG사 계약에 따라 환불 수수료가 부과됩니다.', 'pgall-for-woocommerce' ); ?></h4>
    <p>환불 은행(코드)</p>
    <p>
        <select id="vbank_refund_bank_code" class="wc-enhanced-select enhanced" title="환불처리할 은행을 선택해주세요." <?php echo $disabled; ?>>
            <option value="">환불은행을 선택하세요.</option>
			<?php foreach ( $vbank_lists as $code => $name ) : ?>
                <option value="<?php echo $code; ?>" <?php echo $code == $vbank_refund_bank_code ? 'selected' : ''; ?>><?php echo $name; ?></option>
			<?php endforeach; ?>
        </select>
    </p>
    <p>환불 계좌번호</p>
    <p>
        <input type="text" id="vbank_refund_acc_num" placeholder="번호(숫자)만 입력하세요." value="<?php echo $vbank_refund_acc_num; ?>" <?php echo $disabled; ?>>
    </p>
    <p>환불 계좌주명</p>
    <p>
        <input type="text" id="vbank_refund_acc_name" placeholder="환불 계좌주명" value="<?php echo $vbank_refund_acc_name; ?>" <?php echo $disabled; ?>>
    </p>
    <p>취소사유</p>
    <p>
        <input type="text" id="vbank_refund_reason" placeholder="취소 사유" value="<?php echo $vbank_refund_reason; ?>" <?php echo $disabled; ?>>
    </p>
</div>

<div class="pafw_button_wrapper">
    <input type="button" class="button pafw_action_button tips" id="pafw-vbank-refund-request" value="<?php _e( '환불하기', 'pgall-for-woocommerce' ); ?>" data-tip="이니시스 가상계좌 환불 처리를 수행합니다." <?php echo $refundable ? '' : 'disabled'; ?>>
</div>
