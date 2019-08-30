<?php

global $wp;

$order_id = $wp->query_vars['pafw-ex'];

wp_enqueue_style( 'pafw-ex', PAFW()->plugin_url() . '/assets/css/myaccount.css', array (), PAFW_VERSION );
wp_enqueue_script( 'pafw-ex', PAFW()->plugin_url() . '/assets/js/myaccount.js', array ( 'jquery' ), PAFW_VERSION );
wp_localize_script( 'pafw-ex', '_pafw_ex', array (
	'ajaxurl'  => admin_url( 'admin-ajax.php' ),
	'action'   => PAFW()->slug() . '-request_exchange_return',
	'_wpnonce' => wp_create_nonce( 'request_exchange_return' )
) );

?>

<div id="pafw_ex_wrapper" class="ui mfs_form ">
    <form class="pafw-ex-request ui form" data-id="5439" data-error_popup="no" data-type="" name="mser-request" onsubmit="return false;">

		<?php do_action( 'pafw_before_exchange_return_request', $order_id ); ?>

		<?php
		do_action( 'pafw_exchange_return_request', $order_id );
		?>

		<?php do_action( 'pafw_after_exchange_return_request', $order_id ); ?>

        <input type="hidden" name="redirect_url" value="<?php echo isset( $redirect_url ) ? $redirect_url : ''; ?>">
    </form>
</div>