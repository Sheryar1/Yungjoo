<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$who_exchange_return = new WP_User( $exchange_return->post->post_author );
?>
<tr class="refund <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-order_refund_id="<?php echo pafw_get_object_property( $exchange_return, 'id' ); ?>">
    <td class="ex-type">
		<?php
		if ( $exchange_return->is_exchange() ) {
			$type = esc_attr__( '교환신청', 'pgall-for-woocommerce' );
		} else {
			$type = esc_attr__( '반품신청', 'pgall-for-woocommerce' );
		}
		echo sprintf( __('<span class="ex-type">%s <span class="ex-id">(#%s)</span></span>','pgall-for-woocommerce'), $type, pafw_get_object_property( $exchange_return, 'id' ) );
		echo sprintf( __('<br><span class="ex-time">%s</span>','pgall-for-woocommerce') , date_i18n( 'Y-m-d H:i', strtotime( $exchange_return->post->post_date ) ) );
		?>
    </td>
    <td class="ex-items">
    <?php
		foreach ( $exchange_return->get_items() as $key => $item ) {
			$product_id      = ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
			$product         = wc_get_product( $product_id );
			$order_item      = new WC_Order_Item_Meta( $item );
			$order_item_meta = $order_item->display( true, true );

			echo '<div class="exchange-return-items">';
			printf( '<a href="%s">%1s</a> x %2s개', get_permalink( $product->get_id() ), $product->get_title(), $item['qty'] );
			if ( ! empty( $order_item_meta ) ) {
				printf( '<br><span class="item_meta">%s</span>', $order_item_meta );
			}
			echo '</div>';
		}
		?>
    </td>
    <td class="ex-reason">
		<?php if ( $exchange_return->get_reason() ) : ?>
            <p class="exchange-return-requests"><?php echo str_replace( "\n", "<br>", esc_html( $exchange_return->get_reason() ) ); ?></p>
		<?php endif; ?>
        <input type="hidden" class="order_refund_id" name="order_refund_id[]" value="<?php echo esc_attr( pafw_get_object_property( $exchange_return, 'id' ) ); ?>"/>
    </td>
    <td class="ex-status">
		<?php echo 'processing' == $exchange_return->get_status()  ? __( '요청', 'pgall-for-woocommerce' ) : __( '완료', 'pgall-for-woocommerce' ); ?>
    </td>

	<?php do_action( 'woocommerce_admin_order_item_values', null, $exchange_return, absint( pafw_get_object_property( $exchange_return, 'id' ) ) ); ?>
</tr>
