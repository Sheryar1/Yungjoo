<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$who_exchange_return = new WP_User( $exchange_return->post->post_author );
?>
<tr class="refund <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-order_refund_id="<?php echo pafw_get_object_property( $exchange_return, 'id' ); ?>">
    <td class="thumb pafw-ex-thumb <?php echo $exchange_return->get_status(); ?>">
        <div></div>
    </td>

    <td class="name" colspan="3">
		<?php
		if ( $exchange_return->is_exchange() ) {
			$type = esc_attr__( '교환신청', 'pgall-for-woocommerce' );
		} else {
			$type = esc_attr__( '반품신청', 'pgall-for-woocommerce' );
		}
		echo $type . ' #' . absint( pafw_get_object_property( $exchange_return, 'id' ) ) . ' - ' . esc_attr( date_i18n( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), strtotime( $exchange_return->post->post_date ) ) );

		if ( $who_exchange_return->exists() ) {
			echo ' ' . esc_attr_x( 'by', 'Ex: Refund - $date >by< $username', 'woocommerce' ) . ' ' . '<abbr class="refund_by" title="' . esc_attr__( 'ID: ', 'woocommerce' ) . absint( $who_exchange_return->ID ) . '">' . esc_attr( $who_exchange_return->display_name ) . '</abbr>';
		}
		?>
		<?php
		foreach ( $exchange_return->get_items() as $key => $item ) {
			$product_id      = ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
			$product         = wc_get_product( $product_id );
			$order_item      = new WC_Order_Item_Meta( $item );
			$order_item_meta = $order_item->display( true, true );

			echo '<div class="exchange-return-items">';
			printf( '<a href="%s">%1s</a> x %2s개', get_edit_post_link( $product->id ), $product->get_title(), $item['qty'] );
			if ( ! empty( $order_item_meta ) ) {
				printf( '<br><span class="item_meta">%s</span>', $order_item_meta );
			}
			echo '</div>';
		}
		?>
		<?php if ( $exchange_return->get_reason() ) : ?>
            <p class="exchange-return-requests"><?php echo str_replace( "\n", "<br>", esc_html( $exchange_return->get_reason() ) ); ?></p>
		<?php endif; ?>
        <input type="hidden" class="order_refund_id" name="order_refund_id[]" value="<?php echo esc_attr( pafw_get_object_property( $exchange_return, 'id' ) ); ?>"/>
    </td>

	<?php do_action( 'woocommerce_admin_order_item_values', null, $exchange_return, absint( pafw_get_object_property( $exchange_return, 'id' ) ) ); ?>

	<?php if ( ( ! isset( $legacy_order ) || ! $legacy_order ) && wc_tax_enabled() ) : for ( $i = 0; $i < count( $order_taxes ); $i ++ ) : ?>
        <td class="line_tax" width="1%"></td>
	<?php endfor; endif; ?>

    <td class="pafw-actions">
		<?php
		$order_id = wp_get_post_parent_id( pafw_get_object_property( $exchange_return, 'id' ) );

		$order = wc_get_order( $order_id );

		if ( 'processing' == $exchange_return->get_status() && in_array( $order->get_status(), array ( 'accept-exchange', 'accept-return' ) ) ) {
			$items = array ();

			foreach ( $exchange_return->get_items() as $key => $item ) {
				$items[] = array (
					'item_id' => $item['exchange_return_item_id'],
					'qty'     => $item['qty']
				);
			}

			if ( 'exchange' == pafw_get_meta( $exchange_return, '_type' ) ) {
				echo '<a href="#" data-items="' . esc_attr( json_encode( $items ) ) . '" class="apply-exchange button">' . __( '교환처리', 'pgall-for-woocommerce' ) . '</a>';
			} else {
				echo '<a href="#" data-items="' . esc_attr( json_encode( $items ) ) . '" class="apply-return button">' . __( '반품처리', 'pgall-for-woocommerce' ) . '</a>';
			}
		}
		?>
    </td>
    <td class="wc-order-edit-line-item">
        <div class="wc-order-edit-line-item-actions">
            <a class="delete_refund" href="#"></a>
        </div>
    </td>
</tr>
