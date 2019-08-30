<?php
global $is_IE;

$order = wc_get_order( $order_id );
?>

<h2><?php _e( '에스크로 구매 확인', 'pgall-for-woocommerce' ); ?></h2>

<?php if ( 'yes' == pafw_get_meta( $order, '_pafw_escrow_order_confirm' ) ) : ?>
    <p class="order-info"><?php _e( '구매 확인이 완료되었습니다.', 'pgall-for-woocommerce' ); ?></p>
<?php else: ?>
    <p><?php _e( '구매하신 상품이 배송되었습니다. 상품 수령 후 구매 확인을 해주세요.', 'pgall-for-woocommerce' ); ?></p>
    <div class="pafw-escrow">
        <input type="button" class="button" id="pafw-escrow-purchase-decide" value="<?php _e( '구매 확인', 'pgall-for-woocommerce' ); ?>"/>
    </div>
<?php endif; ?>
