<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action( 'pafw-before-exchange-return-items' ); ?>

<div class="field">
    <label><?php printf( __( '%s할 상품을 선택하세요.' ), PAFW_Exchange_Return_Manager::get_label() ); ?></label>
    <label>상품, 수량 단위로 반품/교환 요청하실 수 있습니다.</label>
</div>

<div class="field">
    <div id="mser_item_container">
        <div class="cart-item-wrap">
            <div class="cart-item-header">
                <div class="product-name"><?php _e( '상품명', 'pgall-for-woocommerce' ); ?></div>
                <div class="product-price"><?php _e( '총가격', 'pgall-for-woocommerce' ); ?></div>
                <div class="product-quantity"><?php _e( '신청수량', 'pgall-for-woocommerce' ); ?></div>
            </div>
            <div class="cart-item-contents">
				<?php
				$order = wc_get_order( $order_id );

				if ( ! empty( $order ) ) {
					$order_items = $order->get_items();
					$valid_items = PAFW_Exchange_Return_Manager::get_valid_exchange_return_order_items( $order );
					foreach ( $valid_items as $key => $qty ) {
						if ( ! empty( $order_items[ $key ] ) ) {
							$item = $order_items[ $key ];

							$product_id      = ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
							$product         = wc_get_product( $product_id );
							$order_item      = new WC_Order_Item_Meta( $item );
							$order_item_meta = $order_item->display( true, true );

							$image_id = $product->get_image_id();
							?>
                            <div class="cart-item-list">
                                <div class="product-checkbox">
                                    <input type="checkbox" name="order_items[<?php echo $key; ?>]"/>
                                    <label for="order_items[<?php echo $key; ?>]"></label>
                                </div>
                                <div class="product-thumbnail">
                                    <div style="background-image: url('<?php echo wp_get_attachment_image_url( $image_id );?>');"></div>
                                </div>
                                <div class="product-info-wrap">
                                    <div class="product-name">
                                        <span class="item_name"><?php echo $item['name']; ?></span><br>
                                        <span class="price_qty"><?php

											if ( $product->get_price() > 0 ) {
												echo wc_price( $product->get_price() ) . ' X ' . $qty;
											} else {
												echo ' X ' . $qty;
											}
											?>
									</span>
                                        <span class="item_meta">
										<?php if ( ! empty( $order_item_meta ) ) {
											echo $order_item_meta;
										} ?>
									</span>
                                    </div>
                                    <div class="product-price">
										<?php echo $product->get_price_html(); ?>
                                    </div>
                                    <div class="product-quantity refund-count">
										<?php
										if ( $product->is_sold_individually() ) {
											$product_quantity = sprintf( '1 <input type="hidden" name="exchange_return_qty[%s]" value="1" />', $key );
										} else {
											$product_quantity = woocommerce_quantity_input( array (
												'input_name'  => "exchange_return_qty[" . $key . "]",
												'input_value' => $qty,
												'max_value'   => $qty,
												'min_value'   => '0'
											), $product, false );
										}

										echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $key, $order_item );
										?>
                                    </div>
                                </div>
                            </div>
							<?php
						}
					}
				}
				?>
            </div>
        </div>
    </div>
</div>

<?php do_action( 'pafw-after-exchange-return-items' ); ?>
