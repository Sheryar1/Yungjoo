<?php

$use_ssl = pafw_check_ssl();

if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
	$lang_code = ICL_LANGUAGE_CODE;

	$request_url       = untrailingslashit( WC()->api_request_url( get_class( $this ) . '?type=std&lang=' . $lang_code, $use_ssl ) );
	$request_close_url = untrailingslashit( WC()->api_request_url( get_class( $this ) . '?type=std_cancel&lang=' . $lang_code, $use_ssl ) );
	$request_popup_url = untrailingslashit( WC()->api_request_url( get_class( $this ) . '?type=std_popup&lang=' . $lang_code, $use_ssl ) );
} else {
	$request_url       = untrailingslashit( WC()->api_request_url( get_class( $this ) . '?type=std', $use_ssl ) );
	$request_close_url = untrailingslashit( WC()->api_request_url( get_class( $this ) . '?type=std_cancel', $use_ssl ) );
	$request_popup_url = untrailingslashit( WC()->api_request_url( get_class( $this ) . '?type=std_popup', $use_ssl ) );
}

?>
<form id="SendPayForm_id" name="" method="POST">
    <!-- 필수사항 -->
    <input style="width:100%;" name="version" value="1.0">
	<?php
	if ( $this->id == 'inicis_stdescrow_bank' ) { ?>
        <input style="width:100%;" name="mid" value="<?php echo $this->settings['escrow_merchant_id']; ?>">
	<?php } else { ?>
        <input style="width:100%;" name="mid" value="<?php echo $this->settings['merchant_id']; ?>">
	<?php } ?>
    <input style="width:100%;" name="goodsname" value="<?php echo esc_attr( $productinfo ); ?>">
    <input style="width:100%;" name="oid" value="<?php echo $txnid; ?>">
    <input style="width:100%;" name="price" value="<?php echo $order->get_total(); ?>">
    <input style="width:100%;" name="currency" value="WON">
    <input style="width:100%;" name="buyername" value="<?php echo pafw_get_object_property( $order, 'billing_last_name' ) . pafw_get_object_property( $order, 'billing_first_name' ); ?>">
    <input style="width:100%;" name="buyertel" value="<?php echo pafw_get_customer_phone_number( $order ); ?>">
    <input style="width:100%;" name="buyeremail" value="<?php echo pafw_get_object_property( $order, 'billing_email' ); ?>">
    <input type="text" style="width:100%;" name="timestamp" value="<?php echo $timestamp; ?>">
    <input type="hidden" style="width:100%;" name="signature" value="<?php echo $sign ?>">
    <input type="hidden" name="mKey" value="<?php echo $mKey; ?>">
    <input style="width:100%;" name="gopaymethod" value="<?php echo $this->settings['gopaymethod']; ?>">
    <input style="width:100%;" name="acceptmethod" value="<?php echo $acceptmethod; ?>">
    <input style="width:100%;" name="returnUrl" value="<?php echo $request_url; ?>">
    <input style="width:100%;" name="nointerest" value="<?php echo $cardNoInterestQuota; ?>">
    <input style="width:100%;" name="quotabase" value="<?php echo $cardQuotaBase; ?>">
    <input style="width:100%;" name="merchantData" value="<?php echo $notification; ?>">
    <!-- 선택사항 -->
    <input style="width:100%;" name="offerPeriod" value="">
    <input style="width:100%;" name="languageView" value="<?php echo pafw_get( $this->settings, 'language_code', 'ko' ); ?>">
    <input style="width:100%;" name="charset" value="UTF-8">
    <input style="width:100%;" name="payViewType" value="<?php echo $payView_type; ?>">
    <input style="width:100%;" name="closeUrl" value="<?php echo $request_close_url; ?>">
    <input style="width:100%;" name="popupUrl" value="<?php echo $request_popup_url; ?>">
    <input style="width:100%;" name="vbankRegNo" value="">
    <input style="width:100%;" name="logo_url" value="<?php echo utf8_uri_encode( pafw_get( $this->settings, 'site_logo', PAFW()->plugin_url() . '/assets/images/default-logo.jpg' ) ); ?>">
	<?php echo apply_filters( 'inicis_payment_form_std_template', '', $order ); ?>
</form>