<?php
global $wp;
?>
<hr>
<script>
    jQuery( document ).ready( function ( $ ) {
        $( 'button.pafw-request-ex' ).on( 'click', function () {
            $( 'div.pafw-ex-wrapper' ).css( 'display', 'block' );
        } );
    } );
</script>
<div style="text-align: center;">
    <button class="button button-primary pafw-request-ex"><?php _e( "교환/반품 신청하기", "pgall-for-woocommerce" ); ?></button>
</div>

<div class="pafw-ex-wrapper" style="display: none;">
	<?php wc_get_template( 'myaccount/exchange-return-request.php', array ( 'redirect_url' => home_url( $wp->request ) ), '', PAFW()->template_path() ); ?>
</div>
