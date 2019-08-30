<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="pafw-exchage-return-section">
    <h2><?php echo sprintf( __( '%s 신청내역', 'pgall-for-woocommerce' ), PAFW_Exchange_Return_Manager::get_label() ); ?></h2>

	<?php if ( count( $exchange_returns ) > 0 ) : ?>
        <table class="pafw-exchage-returns woocommerce-table woocommerce-table--order-details shop_table order_details">
            <thead>
            <tr>
                <th class="woocommerce-table__ex-table ex-type">요청구분</th>
                <th class="woocommerce-table__ex-table ex-items">상품</th>
                <th class="woocommerce-table__ex-table ex-reason">사유</th>
                <th class="woocommerce-table__ex-table ex-status">상태</th>
            </tr>
            </thead>
			<?php
			foreach ( $exchange_returns as $exchange_return ) {
				include( 'exchange-return-details-item.php' );
			}
			?>
        </table>
	<?php else : ?>
        <p><?php _e( '신청 내역이 없습니다.', 'pgall-for-woocommerce' ); ?></p>
	<?php endif; ?>
</div>