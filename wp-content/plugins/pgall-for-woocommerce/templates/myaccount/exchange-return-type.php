<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php do_action( 'pafw-before-exchange-return-type' ); ?>

<?php if ( PAFW_Exchange_Return_Manager::support_exchange() && PAFW_Exchange_Return_Manager::support_return() ) : ?>
    <div class="field pafw-ex-type">
        <label><?php _e( '신청 유형을 선택하세요.', 'pgall-for-woocommerce' ); ?></label>
        <div id="pafw_type_container">
            <div class="return_wrap">
                <input type="radio" name="type" value="return" checked>
                <label><img src="<?php echo PAFW()->plugin_url() . '/assets/images/m_icon_check.png'; ?>"><?php _e( '반품', 'mshop-exchange-return' ); ?></label>
            </div>
            <div class="exchange_wrap">
                <input type="radio" name="type" value="exchange">
                <label><img src="<?php echo PAFW()->plugin_url() . '/assets/images/m_icon_check.png'; ?>"><?php _e( '교환', 'mshop-exchange-return' ); ?></label>
            </div>
        </div>
    </div>
<?php elseif ( PAFW_Exchange_Return_Manager::support_exchange() ) : ?>
    <input type="hidden" name="type" value="exchange">
<?php else: ?>
    <input type="hidden" name="type" value="return">
<?php endif; ?>

<?php do_action( 'pafw-after-exchange-return-type' ); ?>

