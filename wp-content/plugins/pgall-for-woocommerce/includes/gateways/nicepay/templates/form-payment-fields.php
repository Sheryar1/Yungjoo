<?php
$uid = uniqid( 'pafw_nicepay_' );

if ( is_user_logged_in() && 'user' == pafw_get( $gateway->settings, 'management_batch_key', 'subscription' ) ) {
	$bill_key = get_user_meta( get_current_user_id(), '_pafw_bill_key', true );

	if ( ! empty( $bill_key ) ) {
		$issue_nm = get_user_meta( get_current_user_id(), '_pafw_card_name', true );
		$pay_id   = get_user_meta( get_current_user_id(), '_pafw_card_num', true );
		$pay_id   = substr_replace( $pay_id, '********', 4, 8 );
		$pay_id   = implode( '-', str_split( $pay_id, 4 ) );

	}
}

?>

<script>
    jQuery(document).ready(function ($) {
        $('.msspg-input-fields .msspg_cust_type').on('change', function () {
            if (this.checked) {
                $('.msspg-fields-wrap .cust-type', $(this).closest('.msspg-input-fields')).html($(this).val());
            }
        });

        $('input.change-card').on('click', function () {
            var $wrapper = $(this).closest('div.nicepay_billing_wrappper');
            $('div.billing_info', $wrapper).css('display', 'none');
            $('div.msspg-input-fields', $wrapper).css('display', 'block');
            $('input[name=nicepay_issue_bill_key]').val('yes');
        });
    });
</script>

<div class="nicepay_billing_wrappper">
	<?php if ( ! empty( $bill_key ) ) : ?>
    <div class="billing_info">
        <span style="margin-right: 20px;"><?php echo $issue_nm . ' ( ' . $pay_id . ' ) '; ?></span>
        <input type="button" class="button change-card" style="margin: 0 !important;" value="카드변경">
    </div>
	<?php endif; ?>
    <input type="hidden" name="msspg" value="1">
    <input type="hidden" name="nicepay_issue_bill_key" value="<?php echo empty( $bill_key ) ? 'yes' : 'no'; ?>">
    <div class="msspg-input-fields" style="<?php echo ! empty( $bill_key ) ? 'display:none' : ''; ?>">
        <div class="msspg-fields-wrap">
            <div class="label">카드종류</div>
            <div class="cust_type">
                <div class="item">
                    <input type="radio" id='kcp_cust_type_p<?php echo $uid; ?>' class='msspg_cust_type' name="msspg_cust_type" value="법정 생년월일 (주민번호 앞 6자리)" checked>
                    <label for="kcp_cust_type_p<?php echo $uid; ?>">개인카드</label>
                    <div class="check"></div>
                </div>

                <div class="item">
                    <input type="radio" id="kcp_cust_type_c<?php echo $uid; ?>" class='msspg_cust_type' name="msspg_cust_type" value="사업자번호">
                    <label for="kcp_cust_type_c<?php echo $uid; ?>">법인카드</label>
                    <div class="check"></div>
                </div>
            </div>
        </div>
        <div class="msspg-fields-wrap">
            <div class="label">카드번호</div>
            <div>
                <div class="pay_id">
                    <input type="text" maxlength="4" size="4" name="pafw_card_1" value="">
                    <span>-</span>
                    <input type="text" maxlength="4" size="4" name="pafw_card_2" value="">
                    <span>-</span>
                    <input type="password" maxlength="4" size="4" name="pafw_card_3" value="">
                    <span>-</span>
                    <input type="password" maxlength="4" size="4" name="pafw_card_4" value="">
                </div>
            </div>
        </div>
        <div class="msspg-fields-wrap">
            <div class="label">유효기간</div>
            <div>
                <div class="expiry">
                    <select name="pafw_expiry_month">
						<?php
						for ( $i = 1; $i <= 12; $i ++ ) {
							echo sprintf( '<option value="%02d">%02d</option>', $i, $i );
						}
						?>
                    </select>
                    <span>/</span>
                    <select name="pafw_expiry_year">
						<?php
						for ( $i = 0; $i <= 10; $i ++ ) {
							echo sprintf( '<option value="%04d">%04d</option>', intval( date( 'Y' ) ) + $i, intval( date( 'Y' ) ) + $i );
						}
						?>
                    </select>
                </div>
            </div>
        </div>
        <div class="msspg-fields-wrap">
            <div class="label cust-type">법정 생년월일 (주민번호 앞 6자리)</div>
            <div>
                <div class="cert_no">
                    <input type="text" maxlength="10" size="10" name="pafw_cert_no" value="">
                </div>
            </div>
        </div>
        <div class="msspg-fields-wrap cust-type cust-type-P">
            <div class="label">카드 비밀번호 (앞 2자리)</div>
            <div>
                <div class="cert_no">
                    <input type="password" maxlength="2" size="2" name="pafw_card_pw" value="">
                </div>
            </div>
        </div>
    </div>
</div>
