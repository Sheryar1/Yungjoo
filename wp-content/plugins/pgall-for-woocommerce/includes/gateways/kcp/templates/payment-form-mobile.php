<?php

?>

<form id="kpay_mobile_form" name="kpay_mobile_form" method="post" action="https://devpggw.kcp.co.kr:8100/jsp/encodingFilter/encodingFilter.jsp">
    <input type="hidden" name="ordr_idxx" value="<?php echo pafw_get_object_property( $order, 'id' ); ?>"/>
    <input type="hidden" name="good_name" value="<?php echo $this->make_product_info( $order ); ?>"/>
    <input type="hidden" name="good_mny" value="<?php echo $order->get_total(); ?>"/>
    <input type="hidden" name="buyr_name" value="<?php echo pafw_get_object_property( $order, 'billing_last_name' ) . pafw_get_object_property( $order, 'billing_first_name' ); ?>"/>
    <input type="hidden" name="buyr_mail" value="<?php echo pafw_get_object_property( $order, 'billing_email' ); ?>"/>
    <input type="hidden" name="buyr_tel1" value="<?php echo pafw_get_customer_phone_number( $order ); ?>"/>
    <input type="hidden" name="buyr_tel2" value="<?php echo pafw_get_customer_phone_number( $order ); ?>"/>
    <input type="hidden" name="req_tx" value="pay"/>
    <input type="hidden" name="site_cd" value="<?php echo $this->kcpfw_option( 'site_cd' ); ?>"/>
    <input type="hidden" name="currency" value="410"/>
    <input type="hidden" name="approval_key" value="<?php echo $approval_key; ?>"/>
    <input type="hidden" name="pay_method" value="<?php echo $paymentMethodInfo['pay_method'] ; ?>"/>
    <input type="hidden" name="van_code" value="<?php echo $paymentMethodInfo['van_code']; ?>"/>

    <input type="hidden" name="escw_used" value="<?php echo $this->is_escrow ? 'Y' :'N'; ?>">
	<?php if( $this->is_escrow ) : ?>
        <input type="hidden" name="pay_mod" value="O">
        <input type="hidden" name="deli_term" value="5">
        <input type="hidden" name="bask_cntx" value="<?php echo $order->get_item_count(); ?>">
		<?php
		$idx = 1;
		$good_infos = array();
		$order_id = pafw_get_object_property( $order, 'id' );
		foreach( $order->get_items() as $item_id => $item ) {
			$info = array();
			$info[] = 'seq=' . $idx++;
			$info[] = 'ordr_numb=' . $order_id . '_' . $item_id;
			$info[] = 'good_name=' . $item['name'];
			$info[] = 'good_cntx=' . $item['qty'];
			$info[] = 'good_amtx=' . $item['line_total'];
			$good_infos[] = implode( chr(31), $info );
		}

		$good_info = implode( chr(30), $good_infos );
		?>
        <input type="hidden" name="good_info" value="<?php echo $good_info; ?>">
	<?php endif; ?>

    <input type="hidden" name="ActionResult" value="<?php echo $this->kcpfw_option( 'mobile_paymethod' ); ?>"/>
    <input type="hidden" name="tablet_size" value="1.0"/>
    <!-- 가상계좌 설정 -->
    <input type="hidden" name="encoding_trans" value="UTF-8"/>
    <input type="hidden" name="PayUrl" value="<?php echo $pay_url; ?>"/>

    <!-- 가맹점에서 관리하는 고객 아이디 설정을 해야 합니다.(필수 설정) -->
    <input type="hidden" name="shop_user_id" value="<?php echo get_current_user_id(); ?>"/>

    <!-- 복지포인트 결제시 가맹점에 할당되어진 코드 값을 입력해야합니다.(필수 설정) -->
    <input type="hidden" name="pt_memcorp_cd" value=""/>
    <input type="hidden" name="Ret_URL" value="<?php echo $this->get_api_url('payment')?>"/>
    <input type="hidden" name="param_opt_1" value="<?php echo pafw_get_object_property( $order, 'id' ); ?>"/>
    <input type="hidden" name="param_opt_2" value="<?php echo pafw_get_object_property( $order, 'order_key' ); ?>"/>
    <input type="hidden" name="param_opt_3" value="<?php echo $_SERVER['HTTP_REFERER']; ?>"/>

    <!-- 무이자 옵션
		※ 설정할부    (가맹점 관리자 페이지에 설정 된 무이자 설정을 따른다)                             - "" 로 설정
		※ 일반할부    (KCP 이벤트 이외에 설정 된 모든 무이자 설정을 무시한다)                           - "N" 로 설정
		※ 무이자 할부 (가맹점 관리자 페이지에 설정 된 무이자 이벤트 중 원하는 무이자 설정을 세팅한다)   - "Y" 로 설정
	-->
    <input type="hidden" name="kcp_noint" value="<?php echo str_replace( '-', '', $this->kcpfw_option( 'kcp_noint' ) ); ?>"/>
	<?php if ( 'Y' == $this->kcpfw_option( 'kcp_noint' ) ) : ?>
		<?php
		$kcp_noint_quota = array ();
		$options         = json_decode( $this->kcpfw_option( 'kcp_noint_quota' ) );
		foreach ( $options as $option ) {
			$kcp_noint_quota[] = $option->card_company . '-' . implode( ':', explode( ',', $option->month ) );
		}
		?>
        <input type="hidden" name="kcp_noint_quota" value="<?php echo implode( ',', $kcp_noint_quota ); ?>"/>
	<?php endif; ?>

    <!-- 가상계좌 입금 기한 설정하는 파라미터 -->
    <input type="hidden" name="ipgm_date" value="<?php echo date( 'Ymd', strtotime( "+ " . $this->settings['vcnt_expire_term'] . " day" ) ); ?>"/>

    <input type="hidden" name="disp_tax_yn" value="<?php echo 'yes' == $this->settings['disp_tax_yn'] ? 'Y' : 'N'; ?>"/>
    <input type="hidden" name="site_logo" value="<?php echo $this->settings['site_logo']; ?>"/>
    <input type="hidden" name="eng_flag" value="<?php echo $this->settings['eng_flag']; ?>"/>
    <input type="hidden" name="skin_indx" value="<?php echo $this->settings['skin_indx']; ?>"/>
</form>

