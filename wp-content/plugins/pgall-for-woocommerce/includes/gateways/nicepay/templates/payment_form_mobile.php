<form name="tranMgr" method="post" target="_self" action="https://web.nicepay.co.kr/smart/paySmart.jsp" accept-charset="euc-kr">
	<input type="text" name="PayMethod" value="<?php echo strtoupper($this->settings['paymethod']); ?>">
	<input type="text" name="GoodsName" value="<?php echo esc_attr($productinfo); ?>">
	<input type="text" name="GoodsCnt" value="<?php echo $order->get_item_count(); ?>">
	<input type="text" name="Amt" value="<?php echo $order->get_total(); ?>">
	<input type="text" name="BuyerName" value="<?php echo pafw_get_object_property($order, 'billing_last_name') . pafw_get_object_property($order, 'billing_first_name'); ?>">
	<input type="text" name="BuyerTel" value="<?php echo pafw_get_customer_phone_number( $order ); ?>">
	<input type="text" name="Moid" value="<?php echo $txnid; ?>">
	<input type="text" name="MID" value="<?php echo $this->settings['merchant_id']; ?>">
	<input type="hidden" name="ReturnURL" value="<?php echo $return_url; ?>"/>
	<input type="hidden" name="CharSet" value="<?php echo $charset; ?>"/>
	<input type="hidden" name="GoodsCl" value="1"/>
	<?php if( strtoupper($this->settings['paymethod']) == 'VBANK' ) {
		$vbank_date_limit = empty($this->settings['account_date_limit']) ? '3' : $this->settings['account_date_limit'];
		$vbank_date_limit_result = Date('Ymd', strtotime("+{$vbank_date_limit} days"));
		?>
		<input type="hidden" name="VbankExpDate" id="vExp" value="<?php echo $vbank_date_limit_result; ?>"/>
	<?php } else { ?>
		<input type="hidden" name="VbankExpDate" id="vExp"/>
	<?php }?>
	<input type="hidden" name="BuyerEmail" value="<?php echo pafw_get_object_property($order, 'billing_email'); ?>"/>
	<input type="hidden" name="EncryptData" value="<?php echo $hashString; ?>"/>
	<input type="hidden" name="ediDate" value="<?php echo $ediDate; ?>"/>
	<?php if( $this->id == 'nicepay_escrow_bank' ) { ?>
		<input type="hidden" name="TransType" value="1">
	<?php } else { ?>
		<input type="hidden" name="TransType" value="0">
	<?php } ?>
	<?php if( strtoupper($this->settings['paymethod']) == 'CARD' && $this->settings['shopinterest'] == 'yes' ) {    //상점 무이자 사용 여부 설정 ?>
		<input type="hidden" name="ShopInterest" value="1">
		<input type="hidden" name="QuotaInterest" value="<?php echo trim($this->settings['quota_interest']); ?>">
	<?php } ?>
	<?php if( strtoupper($this->settings['paymethod']) == 'BANK' && $this->settings['no_receipt'] == 'yes' ) {    //현금영수증 발행 차단 설정 ?>
		<input type="hidden" name="OptionList" value="no_receipt">
	<?php } ?>
	<?php echo apply_filters('nicepay_payment_form_mobile_template', '', $order); ?>
</form>