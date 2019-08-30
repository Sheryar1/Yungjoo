<?php

defined( 'ABSPATH' ) || exit;

?>

<?php if ( 'yes' == $params['show_terms'] ) : ?>
	<?php if ( apply_filters( 'woocommerce_checkout_show_terms', true ) && function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) ) : ?>

		<?php if ( wc_terms_and_conditions_checkbox_enabled() ) : ?>
            <div class="pafw-instant-payment-section terms-and-conditions">
                <p>
                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                        <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="terms" <?php checked( apply_filters( 'woocommerce_terms_is_checked_default', isset( $_POST['terms'] ) ), true ); // WPCS: input var ok, csrf ok. ?> id="terms"/>
                        <span class="woocommerce-terms-and-conditions-checkbox-text"><?php wc_terms_and_conditions_checkbox_text(); ?></span>&nbsp;<span class="required">*</span>
                    </label>
                    <input type="hidden" name="terms-field" value="1"/>
                </p>
            </div>
		<?php endif; ?>
	<?php endif; ?>
<?php endif; ?>