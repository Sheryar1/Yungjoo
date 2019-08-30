<?php
global $is_IE;

$order = wc_get_order( $order_id );
?>

<h2><?php _e( '배송정보', 'pgall-for-woocommerce' ); ?></h2>
<p><?php printf( __( '배송업체 : %s', 'pgall-for-woocommerce' ), $delivery_company_name ); ?></p>
<p><?php printf( __( '송장번호 : %s', 'pgall-for-woocommerce' ), $delivery_shipping_num ); ?></p>

<h2><?php _e( '구매 확인/거절', 'pgall-for-woocommerce' ); ?></h2>
<?php if ( 'yes' == pafw_get_meta( $order, '_pafw_escrow_order_confirm' ) ) : ?>
    <p class="order-info"><?php  _e( '구매 확인이 완료되었습니다.', 'pgall-for-woocommerce' ); ?></p>
<?php else: ?>
    <div class="pafw-escrow">
		<?php if ( $is_IE ) : ?>
            <script type="text/javascript" src="https://plugin.inicis.com/pay60_escrow.js" charset="euc-kr"></script>
            <script type="text/Javascript">
				if ( typeof StartSmartUpdate === 'function' ) {
					StartSmartUpdate();
				}
				jQuery( '#INIpay' ).css( 'display', 'none' );
            </script>
            <?php if ( $rejected ) : ?>
                <p class="order-info"><?php _e( '구매 거절 처리가 되었습니다. 관리자의 확인 이후에 처리가 완료됩니다. 만약 구매 거절을 철회하고 다시 구매 확인을 하시려는 경우 구매 확인/거절 버튼을 눌러 확인처리를 해주시기 바랍니다.', 'pgall-for-woocommerce' ); ?></p>
            <?php else: ?>
                <p class="order-info"><?php _e( '물품을 받으신 후에 구매 확인 및 거절 처리를 해주세요.', 'pgall-for-woocommerce' ); ?></p>
            <?php endif; ?>
            <p class="order-info">
                <input type="button" class="button" id="pafw-escrow-purchase-decide" value="<?php _e( '구매 확인/거절', 'pgall-for-woocommerce' ); ?>"/>
            </p>
            <form name=ini method=post action="">
                <input type=hidden name=tid value="<?php echo $transaction_id; ?>">
                <input type=hidden name=mid value="<?php echo $merchant_id; ?>"/>
                <input type=hidden name=paymethod value="">
                <input type=hidden name=encrypted value="">
                <input type=hidden name=sessionkey value="">
                <input type=hidden name=version value=5000>
                <input type=hidden name=clickcontrol value="">
                <input type=hidden name=acceptmethod value=" ">
                <input type=hidden name=orderid value="<?php echo $order_id; ?>">
            </form>
		<?php else: ?>
            <p class="order-info"><?php _e( '주문확인 및 거절은 인터넷 익스플로러에서만 가능합니다. 익스플로러에서 진행하여 주십시오.', 'pgall-for-woocommerce' ); ?></p>
		<?php endif; ?>
    </div>
<?php endif; ?>
