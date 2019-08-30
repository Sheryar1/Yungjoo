<?php


?>
    <div class="pafw_payment_info">
        <p>배송정보 등록자</p>
        <p>
            <input type="text" class="mb_inipay_wide tips" id="shipping_writer" value="<?php echo $delivery_register_name; ?>" data-tip="결제 플러그인 설정에서 배송정보 등록자 이름을 지정할수 있습니다." readonly>
        </p>
        <p>택배사명</p>
        <p>
            <input type="text" class="mb_inipay_wide tips" id="shipping_company" value="<?php echo $delivery_company_name; ?>" data-tip="결제 플러그인 설정에서 택배사명을 지정할수 있습니다." readonly>
        </p>
        <p>송장번호</p>
        <p>
            <input type="text" class="mb_inipay_wide" id="tracking_number" placeholder="송장번호" value="<?php echo $tracking_number; ?>" data-tip="송장 번호를 입력해주세요." <?php echo $is_confirmed || ( $register_delivery_info && ! $support_modify_delivery_info ) ? 'readonly' : ''; ?>>
        </p>
    </div>

<?php if ( ! $is_cancelled ) : ?>
    <div class="pafw_button_wrapper">
		<?php if ( ! $is_confirmed ) : ?>
			<?php if ( $register_delivery_info ) : ?>
                <input type="button" class="button pafw_action_button tips" id="pafw-escrow-modify-delivery-info" value="<?php _e( '배송정보 수정', 'pgall-for-woocommerce' ); ?>" data-tip="<?php _e( '배송정보를 수정합니다.', 'pgall-for-woocommerce' ); ?>" <?php echo $support_modify_delivery_info ? '' : 'disabled'; ?>>
			<?php else : ?>
                <input type="button" class="button pafw_action_button tips" id="pafw-escrow-register-delivery-info" value="<?php _e( '배송정보 등록', 'pgall-for-woocommerce' ); ?>" data-tip="<?php _e( '배송정보를 등록합니다.', 'pgall-for-woocommerce' ); ?>">
			<?php endif; ?>
		<?php else: ?>
            <input type="button" class="button pafw_action_button tips" id="pafw-escrow-approve-reject" value="<?php _e( '구매거절확인(환불처리)', 'pgall-for-woocommerce' ); ?>" data-tip="<?php _e( '에스크로 결제를 환불처리를 합니다.', 'pgall-for-woocommerce' ); ?>">
		<?php endif; ?>
    </div>
<?php endif; ?>