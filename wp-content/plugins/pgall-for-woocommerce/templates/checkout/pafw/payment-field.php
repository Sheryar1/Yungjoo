<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
    <div class="payment_box payment_method_<?php echo esc_attr( $gateway->id ); ?>"
	     <?php if ( ! $gateway->chosen ) : /* phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace */ ?>style="display:none;"<?php endif; /* phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace */ ?>>
		<?php $gateway->payment_fields(); ?>
    </div>
<?php endif; ?>

