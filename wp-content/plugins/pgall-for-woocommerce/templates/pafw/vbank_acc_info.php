<?php

if ( $order->get_status() == 'failed' ) {
	return;
}

$vacc_bank_name   = pafw_get_meta( $order, '_pafw_vacc_bank_name' );    //입금은행명/코드
$vacc_num         = pafw_get_meta( $order, '_pafw_vacc_num' );                //계좌번호
$vacc_name        = pafw_get_meta( $order, '_pafw_vacc_holder' );            //예금주
$vacc_input_name  = pafw_get_meta( $order, '_pafw_vacc_depositor' );        //송금자
$vacc_date        = pafw_get_meta( $order, '_pafw_vacc_date' );            //입금예정일
$vacc_date_format = date( __( 'Y년 m월 d일', 'pgall-for-woocommerce' ), strtotime( $vacc_date ) );

if ( 'yes' != pafw_get_meta( $order, '_pafw_sent_vacc_info' ) ) {
	do_action( 'send_vact_info', pafw_get_object_property( $order, 'id' ), pafw_get_customer_phone_number( $order ), $vacc_bank_name, $vacc_num, $vacc_name, $vacc_input_name, $vacc_date_format );
	do_action( 'send_vact_info_v2', pafw_get_object_property( $order, 'id' ), pafw_get_customer_phone_number( $order ), $vacc_bank_name, $vacc_num, $vacc_name, $vacc_input_name, $vacc_date_format );
	pafw_update_meta_data( $order, '_pafw_sent_vacc_info', 'yes' );
}

?>

<h2><?php _e( '가상계좌 무통장입금 안내', 'pgall-for-woocommerce' ); ?></h2>
<p><?php _e( '가상계좌 무통장입금 안내로 주문이 접수되었습니다. 아래 지정된 계좌번호로 입금기한내에 반드시 입금하셔야 하며, 송금자명으로 입금 해주셔야 주문이 정상 접수 됩니다.', 'pgall-for-woocommerce' ); ?></p>

<div id="inicis_vbank_account_table_wrap">
    <table id="inicis_vbank_account_table" class="inicis_vbank_account_table">
        <tbody>
        <tr>
            <td><?php _e( '은행명:', 'pgall-for-woocommerce' ); ?></td>
            <td data-title="<?php _e( '은행명:', 'pgall-for-woocommerce' ); ?>"><?php echo $vacc_bank_name; ?></td>
        </tr>
        <tr>
            <td><?php _e( '계좌번호:', 'pgall-for-woocommerce' ); ?></td>
            <td data-title="<?php _e( '계좌번호:', 'pgall-for-woocommerce' ); ?>"><?php echo $vacc_num; ?></td>
        </tr>
        <?php if( ! empty( $vacc_name ) ) : ?>
        <tr>
            <td><?php _e( '예금주:', 'pgall-for-woocommerce' ); ?></td>
            <td data-title="<?php _e( '예금주:', 'pgall-for-woocommerce' ); ?>"><?php echo $vacc_name; ?></td>
        </tr>
        <?php endif; ?>
        <?php if( ! empty( $vacc_input_name ) ) : ?>
        <tr>
            <td><?php _e( '송금자:', 'pgall-for-woocommerce' ); ?></td>
            <td data-title="<?php _e( '송금자:', 'pgall-for-woocommerce' ); ?>"><?php echo $vacc_input_name; ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td><?php _e( '입금기한:', 'pgall-for-woocommerce' ); ?></td>
            <td data-title="<?php _e( '입금기한:', 'pgall-for-woocommerce' ); ?>"><?php echo $vacc_date_format; ?></td>
        </tr>
        </tbody>
    </table>
</div>