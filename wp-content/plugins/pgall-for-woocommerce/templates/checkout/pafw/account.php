<?php
$has_billing_info = false;

if ( is_user_logged_in() ) {
	try {
		$user = new WC_Customer( get_current_user_id() );

		if ( $user && ! empty( $user->get_billing_first_name() ) && ! empty( $user->get_billing_phone() ) ) {
			if ( 'yes' != $params['need_shipping'] || ( ! empty( $user->get_billing_postcode() ) && ! empty( $user->get_billing_address_1() ) ) ) {
				$has_billing_info = true;
			}
		}
	} catch ( Exception $e ) {

	}
}

?>

<div class="pafw-billing-info" style="<?php echo is_user_logged_in() && 'yes' != $params['show_account'] ? 'display: none;' : ''; ?>">
	<?php if ( $has_billing_info ) : ?>
        <div class="pafw-account">
            <div class="customer_info">
				<?php if ( ! empty( $params['account_header'] ) ) : ?>
                    <p class="customer_info_header"><?php echo $params['account_header']; ?></p>
				<?php endif; ?>
                <p class="pafw-billing-name mshop-enable-kr mshop-always-kr"><?php printf( '%s%s ( %s, %s )', $user->get_billing_first_name(), $user->get_billing_last_name(), $user->get_billing_email(), $user->get_billing_phone() ); ?></p>
				<?php if ( 'yes' == $params['need_shipping'] ) : ?>
                    <p class="pafw-billing-address mshop-enable-kr mshop-always-kr"><?php printf( '(%s) %s %s', $user->get_billing_postcode(), $user->get_billing_address_1(), $user->get_billing_address_2() ); ?></p>
				<?php endif; ?>
            </div>
            <div class="edit">
				<?php if ( 'yes' == $params['editable_account_info'] ) : ?>
                    <div class="pafw-change-billing-info"></div>
				<?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="pafw-account">
            <div class="customer_info">
                <p class="customer_info_header"><?php _e('등록된 고객정보가 없습니다. 고객정보를 등록해주세요.', 'pgall-for-woocommerce'); ?></p>
            </div>
            <div class="edit">
                <div class="pafw-change-billing-info"></div>
            </div>
        </div>
	<?php endif; ?>
    <div class='pafw-billing-address' style="display:none">
		<?php if ( ! empty( $params['account_header'] ) ) : ?>
            <p class="customer_info_header"><?php echo $params['account_header']; ?></p>
		<?php endif; ?>
		<?php
		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		do_action( 'pafw_instant_payment_before_form_billing' );

		wc_get_template( 'checkout/pafw/form-billing.php', array ( 'checkout' => WC()->checkout(), 'need_shipping' => $params['need_shipping'], 'params' => $params ), '', PAFW()->template_path() );

		do_action( 'pafw_instant_payment_after_form_billing' );
		?>
    </div>
</div>
