<?php
?>

<div id="kcp_payment_form" style="display:none">
    <input type="hidden" name="order_id" value="<?php echo pafw_get_object_property( $order, 'id' ); ?>"/>
    <input type="hidden" name="order_key" value="<?php echo pafw_get_object_property( $order, 'order_key' ); ?>"/>
    <input type="hidden" name="ordr_idxx" value="<?php echo pafw_get_object_property( $order, 'id' ); ?>"/>
    <input type="hidden" name="good_name" value="<?php echo $this->make_product_info( $order ); ?>"/>
    <input type="hidden" name="good_mny" value="<?php echo $order->get_total(); ?>"/>
    <input type="hidden" name="buyr_name" value="<?php echo pafw_get_object_property( $order, 'billing_last_name' ) . pafw_get_object_property( $order, 'billing_first_name' ); ?>"/>
    <input type="hidden" name="buyr_mail" value="<?php echo pafw_get_object_property( $order, 'billing_email' ); ?>"/>
    <input type="hidden" name="buyr_tel1" value="<?php echo pafw_get_customer_phone_number( $order ); ?>"/>
    <input type="hidden" name="buyr_tel2" value=""/>
    <input type="hidden" name="req_tx" value="pay"/>
    <input type="hidden" name="site_cd" value="<?php echo $this->kcpfw_option( 'site_cd' ); ?>"/>
    <input type="hidden" name="currency" value="WON"/>
    <input type="hidden" name="module_type" value="01"/>
    <input type="hidden" name="pay_method" value="<?php echo $this->kcpfw_option( 'pc_paymethod' ); ?>"/>

    <input type="hidden" name="escw_used" value="<?php echo $this->is_escrow ? 'Y' : 'N'; ?>">
	<?php if ( $this->is_escrow ) : ?>
        <input type="hidden" name="pay_mod" value="O">
        <input type="hidden" name="deli_term" value="5">
        <input type="hidden" name="bask_cntx" value="<?php echo $order->get_item_count(); ?>">
		<?php
		$idx        = 1;
		$good_infos = array ();
		$order_id   = pafw_get_object_property( $order, 'id' );
		foreach ( $order->get_items() as $item_id => $item ) {
			$info         = array ();
			$info[]       = 'seq=' . $idx ++;
			$info[]       = 'ordr_numb=' . $order_id . '_' . $item_id;
			$info[]       = 'good_name=' . $item['name'];
			$info[]       = 'good_cntx=' . $item['qty'];
			$info[]       = 'good_amtx=' . $item['line_total'];
			$good_infos[] = implode( chr( 31 ), $info );
		}

		$good_info = implode( chr( 30 ), $good_infos );
		?>
        <input type="hidden" name="good_info" value="<?php echo $good_info; ?>">
	<?php endif; ?>

    <!-- 필수 항목 : Payplus Plugin에서 값을 설정하는 부분으로 반드시 포함되어야 합니다. 값을 설정하지 마십시오. -->
    <input type="hidden" name="res_cd"/>
    <input type="hidden" name="res_msg"/>
    <input type="hidden" name="tno"/>
    <input type="hidden" name="trace_no"/>
    <input type="hidden" name="enc_info"/>
    <input type="hidden" name="enc_data"/>
    <input type="hidden" name="ret_pay_method"/>
    <input type="hidden" name="tran_cd"/>
    <input type="hidden" name="bank_name"/>
    <input type="hidden" name="bank_issu"/>
    <input type="hidden" name="use_pay_method"/>

    <!-- 현금영수증 관련 정보 : Payplus Plugin 에서 설정하는 정보입니다 -->
    <input type="hidden" name="cash_tsdtime"/>
    <input type="hidden" name="cash_yn"/>
    <input type="hidden" name="cash_authno"/>
    <input type="hidden" name="cash_tr_code"/>
    <input type="hidden" name="cash_id_info"/>

    <!-- 2012년 8월 18일 전자상거래법 개정 관련 설정 부분, 제공 기간 설정 0:일회성 1:기간설정(ex 1:2012010120120131) -->
    <input type="hidden" name="good_expr" value="0"/>

    <!-- 가맹점에서 관리하는 고객 아이디 설정을 해야 합니다.(필수 설정) -->
    <input type="hidden" name="shop_user_id" value="<?php echo get_current_user_id(); ?>"/>

    <!-- 복지포인트 결제시 가맹점에 할당되어진 코드 값을 입력해야합니다.(필수 설정) -->
    <input type="hidden" name="pt_memcorp_cd" value=""/>

    <!-- 무이자 옵션
        ※ 설정할부    (가맹점 관리자 페이지에 설정 된 무이자 설정을 따른다)                             - "" 로 설정
        ※ 일반할부    (KCP 이벤트 이외에 설정 된 모든 무이자 설정을 무시한다)                           - "N" 로 설정
        ※ 무이자 할부 (가맹점 관리자 페이지에 설정 된 무이자 이벤트 중 원하는 무이자 설정을 세팅한다)   - "Y" 로 설정
    -->
    <input type="hidden" name="kcp_noint" value="<?php echo str_replace( '-', '', pafw_get( $this->settings, 'kcp_noint' ) ); ?>"/>
	<?php if ( 'Y' == pafw_get( $this->settings, 'kcp_noint' ) ) : ?>
		<?php
		$kcp_noint_quota = array ();
		$options         = json_decode( pafw_get( $this->settings, 'kcp_noint_quota' ) );
		foreach ( $options as $option ) {
			$kcp_noint_quota[] = $option->card_company . '-' . implode( ':', explode( ',', $option->month ) );
		}
		?>
        <input type="hidden" name="kcp_noint_quota" value="<?php echo implode( ',', $kcp_noint_quota ); ?>"/>
	<?php endif; ?>

    <!-- 가상계좌 입금 기한 설정하는 파라미터 -->
    <input type="hidden" name="vcnt_expire_term" value="<?php echo pafw_get( $this->settings, 'vcnt_expire_term' ); ?>"/>
    <input type="hidden" name="vcnt_expire_term_time" value="235959"/>

    <input type="hidden" name="disp_tax_yn" value="<?php echo 'yes' == pafw_get( $this->settings, 'disp_tax_yn' ) ? 'Y' : 'N'; ?>"/>
    <input type="hidden" name="site_logo" value="<?php echo pafw_get( $this->settings, 'site_logo' ); ?>"/>
    <input type="hidden" name="eng_flag" value="<?php echo pafw_get( $this->settings, 'eng_flag' ); ?>"/>
    <input type="hidden" name="skin_indx" value="<?php echo pafw_get( $this->settings, 'skin_indx' ); ?>"/>
</div>

