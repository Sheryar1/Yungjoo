<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action( 'pafw-before-exchange-return-action' ); ?>

<div class="field pafw-ex-action">
    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
    <input type="button" class="reqeust-exchange-return" value="<?php _e( '신청하기', 'pgall-for-woocommerce' ); ?>">
</div>

<?php do_action( 'pafw-after-exchange-return-action' ); ?>
