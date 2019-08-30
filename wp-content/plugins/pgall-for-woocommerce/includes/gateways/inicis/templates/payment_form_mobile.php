<?php

?>

<form id="form1" name="ini" method="post" action="" accept-charset="EUC-KR">
    <input type="hidden" name="inipaymobile_type" id="select" value="web"/>
    <input type="hidden" name="P_OID" value="<?php echo $txnid; ?>"/>
    <input type="hidden" name="P_GOODS" value="<?php echo esc_attr( $productinfo ); ?>"/>
    <input type="hidden" name="P_AMT" value="<?php echo $order->get_total(); ?>"/>
    <input type="hidden" name="P_UNAME" value="<?php echo pafw_get_object_property( $order, 'billing_last_name' ) . pafw_get_object_property( $order, 'billing_first_name' ); ?>"/>
    <input type="hidden" name="P_MNAME" value="<?php echo get_bloginfo( 'name' ); ?>"/>
    <input type="hidden" name="P_MOBILE" value="<?php echo pafw_get_customer_phone_number( $order ); ?>"/>
    <input type="hidden" name="P_EMAIL" value="<?php echo pafw_get_object_property( $order, 'billing_email' ); ?>"/>
	<?php if ( $this->id == 'inicis_stdescrow_bank' ) { ?>
        <input type="hidden" name="P_MID" value="<?php echo $this->settings['escrow_merchant_id']; ?>">
	<?php } else { ?>
        <input type="hidden" name="P_MID" value="<?php echo $this->settings['merchant_id']; ?>">
	<?php } ?>
	<?php
	$use_ssl = pafw_check_ssl();

	if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
		$lang_code  = ICL_LANGUAGE_CODE;
		$next_url   = WC()->api_request_url( get_class( $this ), $use_ssl ) . '?lang=' . $lang_code . '&type=mobile_next';
		$return_url = WC()->api_request_url( get_class( $this ), $use_ssl ) . '?lang=' . $lang_code . '&type=mobile_return,oid=' . $txnid;
		$noti_url   = WC()->api_request_url( get_class( $this ), $use_ssl ) . '?lang=' . $lang_code . '&type=mobile_noti';
		$cancel_url = WC()->api_request_url( get_class( $this ), $use_ssl ) . '?land=' . $lang_code . '&type=mobile_return,oid=' . $txnid;
	} else {
		$next_url   = WC()->api_request_url( get_class( $this ), $use_ssl ) . '?type=mobile_next';
		$return_url = WC()->api_request_url( get_class( $this ), $use_ssl ) . '?type=mobile_return,oid=' . $txnid;
		$noti_url   = WC()->api_request_url( get_class( $this ), $use_ssl ) . '?type=mobile_noti';
		$cancel_url = WC()->api_request_url( get_class( $this ), $use_ssl ) . '?type=mobile_return,oid=' . $txnid;
	}
	?>
    <input type="hidden" name="P_NEXT_URL" value="<?php echo $next_url; ?>">
    <input type="hidden" name="P_RETURN_URL" value="<?php echo $return_url; ?>">
    <input type="hidden" name="P_NOTI_URL" value="<?php echo $noti_url; ?>">
    <input type="hidden" name="P_CANCEL_URL" value="<?php echo $cancel_url; ?>">

    <input type="hidden" name="P_NOTI" value="<?php echo $notification; ?>">
    <input type="hidden" name="P_HPP_METHOD" value="<?php echo empty( $this->settings['hpp_method'] ) ? '2' : $this->settings['hpp_method']; ?>">
    <input type="hidden" name="P_APP_BASE" value="">

	<?php
	if ( strtolower( $this->settings['paymethod'] ) == 'vbank' ) {
		$date_limit = pafw_get( $this->settings, 'account_date_limit', 3 );
		$date       = date( 'Ymd', strtotime( current_time( 'mysql' ) . " +" . $date_limit . " days" ) );
		?>
        <input type="hidden" name="P_VBANK_DT" value="<?php echo $date; ?>">
		<?php
	}
	//모바일 신용카드 할부 설정
	if ( strtolower( $this->settings['paymethod'] ) == 'wcard' ) {
		if ( ! empty( $this->settings['quotabase'] ) ) {
			$quotabase_arr    = explode( ',', $this->settings['quotabase'] );
			$quotabase_option = array ();

			$quotabase_option[] = '01'; //기본으로 일시불 추가
			foreach ( $quotabase_arr as $item ) {
				$quotabase_option[] = sprintf( '%02d', (int) $item );
			}
			sort( $quotabase_option );
			$quotabase_option = implode( ':', $quotabase_option );
			?>
            <input type="hidden" name="P_QUOTABASE" value="<?php echo $quotabase_option; ?>">
		<?php } else { ?>
            <input type="hidden" name="P_QUOTABASE" value="01">
		<?php }
	}
	?>
    <input type="hidden" name="P_RESERVED" value="<?php echo htmlentities( $acceptmethod ); ?>">
    <input type="hidden" name="paymethod" size=20 value="<?php echo $this->settings['paymethod']; ?>"/>
    <img id="inicis_image_btn" src="<?php echo PAFW()->plugin_url() . '/assets/gateways/inicis/images/button_03.gif'; ?>" width="63" height="25" style="width:63px;height:25px;border:none;padding:0px;margin:8px 0px;" onclick="javascript:onSubmit();"/>
	<?php echo apply_filters( 'inicis_payment_form_mobile_template', '', $order ); ?>
</form>