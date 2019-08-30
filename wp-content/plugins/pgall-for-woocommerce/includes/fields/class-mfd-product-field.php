<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class MFD_Product_Field extends MFD_Field {

	public function output( $element, $post, $form ) {
		$type = mfd_get( $element, 'type' );

		if ( 'custom' == $type ) {
			?>
            <div style="<?php echo 'no' == mfd_get( $element, 'show_to_user' ) ? 'display: none;' : ''; ?>">
                <div class="required field">
                    <label><?php _e('결제내용', 'pgall-for-woocommerce'); ?></label>
                    <input type="text" name="order_title" value="<?php echo mfd_get( $element, 'order_title' ); ?>">
                </div>
                <div class="required field">
                    <label><?php _e('결제금액', 'pgall-for-woocommerce'); ?></label>
                    <input type="text" name="order_amount" value="<?php echo mfd_get( $element, 'order_amount' ); ?>">
                </div>
            </div>
			<?php
		} else if ( 'manual' == $type ) {
			?>
            <div style="display: none;">
                <input type="text" name="product_id[]" value="<?php echo mfd_get( $element, 'product_id' ); ?>">
                <input type="text" name="variation_id[]" value="<?php echo mfd_get( $element, 'variation_id' ); ?>">
                <input type="text" name="variation[]" value="<?php echo mfd_get( $element, 'variation' ); ?>">
                <input type="text" name="custom_data[]" value="<?php echo mfd_get( $element, 'custom_data' ); ?>">
            </div>
			<?php
		}
	}
}