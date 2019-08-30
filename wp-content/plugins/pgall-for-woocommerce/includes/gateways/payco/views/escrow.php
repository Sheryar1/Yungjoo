<?php


?>
<div class="pafw_button_wrapper">
    <input type="button" class="button pafw_action_button tips" id="pafw-escrow-register-delivery-info" value="<?php _e( '배송시작', 'pgall-for-woocommerce' ); ?>" data-tip="<?php _e( '배송정보를 등록합니다.', 'pgall-for-woocommerce' ); ?>" <?php echo $is_cancelled || $register_delivery_info || $is_confirmed || ! $is_payed ? 'disabled="disabled"' : ''; ?>>
</div>
