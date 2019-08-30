<?php
global $is_IE;

?>

<h2><?php _e( '에스크로 구매 확인', 'pgall-for-woocommerce' ); ?></h2>
<?php if ( 'yes' == pafw_get_meta( $order, '_pafw_escrow_order_confirm' ) ) : ?>
    <p><?php printf( __( '배송업체 : %s', 'pgall-for-woocommerce' ), $delivery_company_name ); ?></p>
    <p><?php printf( __( '송장번호 : %s', 'pgall-for-woocommerce' ), $delivery_shipping_num ); ?></p>
    <p class="order-info"><?php  _e( '구매 확인이 완료되었습니다.', 'pgall-for-woocommerce' ); ?></p>
<?php else: ?>
    <p><?php printf( __( '배송업체 : %s', 'pgall-for-woocommerce' ), $delivery_company_name ); ?></p>
    <p><?php printf( __( '송장번호 : %s', 'pgall-for-woocommerce' ), $delivery_shipping_num ); ?></p>
    <p class="order-info"><?php  _e( '배송 안내 메일이 발송되었습니다. 메일을 확인하신 후 구매 확인을 진행해주세요.', 'pgall-for-woocommerce' ); ?></p>
<?php endif; ?>
