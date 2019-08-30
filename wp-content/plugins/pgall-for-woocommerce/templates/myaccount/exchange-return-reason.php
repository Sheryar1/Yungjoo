<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$reasons = array (
	__( '구매의사취소', 'pgall-for-woocommerce' ),
	__( '색상 및 사이즈 변경', 'pgall-for-woocommerce' ),
	__( '다른 상품 잘못 주문', 'pgall-for-woocommerce' ),
	__( '서비스 불만족', 'pgall-for-woocommerce' ),
	__( '상품정보 상이', 'pgall-for-woocommerce' ),
	__( '제품불량', 'pgall-for-woocommerce' ),
	__( '배송지연', 'pgall-for-woocommerce' ),
)

?>

<div class="field pafw-ex-reason">
    <select class="pafw-ex-reason">
        <option selected="selected"><?php _e( '교환/반품 사유를 입력해주세요.', 'pgall-for-woocommerce' ); ?></option>
		<?php foreach ( $reasons as $reason ) : ?>
            <option><?php echo $reason; ?></option>
		<?php endforeach; ?>
    </select>

    <textarea name="reason" class="input-text"></textarea>
</div>