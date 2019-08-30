<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

wp_register_style( 'pafw-nicepay-form-fields', PAFW()->plugin_url() . '/assets/gateways/nicepay/css/form-fields.css' );
wp_enqueue_style( 'pafw-nicepay-form-fields' );

$issue_nm = get_user_meta( get_current_user_id(), '_pafw_card_name', true );
$pay_id   = get_user_meta( get_current_user_id(), '_pafw_card_num', true );
$pay_id   = substr_replace( $pay_id, '********', 4, 8 );
$pay_id   = implode( '-', str_split( $pay_id, 4 ) );

?>
<div class="msspg-myaccount">
	<h1>결제 카드 관리</h1>
	<ul class="cks-notice">
		<li>카드 등록은 1개만 가능합니다.</li>
	</ul>

	<div class="cks-card">
		<p>사용중인 카드 정보</p>
		<h5><?php printf( "%s ( %s )", $issue_nm, $pay_id ); ?></h5>
	</div>

	<a class="button button-primary cks-btn" href="/my-account/pafw-card-register/">카드 변경</a>
	<p class="button button-primary cks-btn pafw_delete_card">카드 삭제
	<p/>
</div>
