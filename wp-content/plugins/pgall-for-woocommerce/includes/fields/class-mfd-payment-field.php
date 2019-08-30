<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class MFD_Payment_Field extends MFD_Field {
	protected static $is_enqueued = false;

	function save_meta( $element = null ) {
		return true;
	}

	public function enqueue_payment_scripts() {
		if ( ! self::$is_enqueued ) {
			PAFW()->wp_enqueue_scripts( true );
			self::$is_enqueued = true;
		}
	}

	public function output( $element, $post, $form ) {
		$classes = mfd_make_class( array (
			'yes' == mfd_get( $element, 'required' ) ? 'required' : '',
			mfd_get( $element, 'class' ),
			mfd_get( $element, 'width' ),
			'msaddr_widget field'
		) );

		$payment_method = mfd_get( $element, 'payment_method' );

		if ( empty( $payment_method ) ) {
			return;
		}

		$payment_method = key( $payment_method );

		$class_name = 'WC_Gateway_' . ucwords( $payment_method, '_' );

		if ( class_exists( $class_name ) ) {
			$this->enqueue_payment_scripts();

			$uid = uniqid( 'pafw_' );

			?>
            <script>
                jQuery(document).ready(function ($) {
                    var $wrapper = $('div[data-id=<?php echo $uid; ?>]');
                    var $form = $wrapper.closest('form');

                    $('input.pafw-simple-payment', $wrapper).on('click', function () {
                        if ($form.form('is valid')) {
                            $('input[name=payment_method]', $wrapper).attr('checked', 'checked');
                            var payment_method = $('input[name=payment_method]:checked', $form).val();
                            $form.triggerHandler('checkout_place_order_' + payment_method);
                        } else {
                            $('input', $form).blur();
                        }
                    });
                });
            </script>
            <div class="<?php echo $classes; ?>" data-id="<?php echo $uid; ?>">
                <div style="display: none;">
                    <input type="radio" name="payment_method" value="<?php echo $payment_method; ?>" checked>
                </div>
                <input type="button" class="<?php echo mfd_get( $element, 'class' ); ?> ui button pafw-simple-payment" value="<?php echo mfd_get( $element, 'title' ); ?>">
            </div>
			<?php
		}
	}

	public function update_meta( $id, $updator, $params, $args ) {
		$updator( $id, $this->id . '_postcode', urldecode( mfd_get( $params, $this->id . '_postcode' ) ) );
		$updator( $id, $this->id . '_address_1', urldecode( mfd_get( $params, $this->id . '_address_1' ) ) );
		$updator( $id, $this->id . '_address_2', urldecode( mfd_get( $params, $this->id . '_address_2' ) ) );
		$updator( $id, 'mshop_' . $this->id . '_address-postnum', urldecode( mfd_get( $params, $this->id . '_postcode' ) ) );
		$updator( $id, 'mshop_' . $this->id . '_address-addr1', urldecode( mfd_get( $params, $this->id . '_address_1' ) ) );
		$updator( $id, 'mshop_' . $this->id . '_address-addr2', urldecode( mfd_get( $params, $this->id . '_address_2' ) ) );
	}

}