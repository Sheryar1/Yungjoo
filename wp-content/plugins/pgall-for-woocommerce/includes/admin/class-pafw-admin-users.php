<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Admin_Users' ) ) {

	class PAFW_Admin_Users {

		public function __construct() {
			add_action( 'init', array ( $this, 'init' ) );
			add_action( 'init', array ( $this, 'delete_card' ) );
		}

		public function init() {
			add_filter( 'manage_users_columns', array ( $this, 'manage_users_columns' ), 999 );
			add_filter( 'manage_users_custom_column', array ( $this, 'manage_users_custom_column' ), 10, 3 );
		}

		function manage_users_custom_column( $value, $column_name, $user_id ) {
			if ( 'pafw_card_info' == $column_name ) {
				$bill_key = get_user_meta( $user_id, '_pafw_bill_key', true );

				if ( ! empty( $bill_key ) ) {
					$issue_nm = get_user_meta( $user_id, '_pafw_card_name', true );
					$pay_id   = get_user_meta( $user_id, '_pafw_card_num', true );
					$pay_id   = substr_replace( $pay_id, '********', 4, 8 );
					$pay_id   = implode( '-', str_split( $pay_id, 4 ) );

					$url = wp_nonce_url( add_query_arg( array ( 'user_id' => $user_id, 'action' => 'pafw_delete_card' ), remove_query_arg( array ( 'user_id', 'action' ) ) ), 'pafw_delete_card' );

					ob_start();
					?>
                    <p><?php printf( "%s ( %s )", $issue_nm, $pay_id ); ?></p>
                    <a href="<?php echo $url; ?>" class="button" onclick="return confirm('등록된 카드 정보를 삭제하시겠습니까?');">삭제하기</a>
					<?php
					return ob_get_clean();
				}
			}

			return $value;
		}

		function manage_users_columns( $users_columns ) {
			$users_columns['pafw_card_info'] = __( '카드정보', 'pgall-for-woocommerce' );

			return $users_columns;
		}

		function delete_card() {
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'pafw_delete_card' ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_users' ) ) {
				return;
			}

			if ( isset( $_GET['action'] ) && isset( $_GET['user_id'] ) && 'pafw_delete_card' == $_GET['action'] ) {
				$gateway = pafw_get_payment_gateway( 'nicepay_subscription' );


				if ( $gateway ) {
					$user_id  = $_GET['user_id'];
					$bill_key = get_user_meta( $user_id, '_pafw_bill_key' );

					if ( ! empty( $bill_key ) ) {
						try {
							$gateway->cancel_bill_key( $bill_key );
						} catch ( Exception $e ) {

						}
						delete_user_meta( $user_id, '_pafw_payment_method' );
						delete_user_meta( $user_id, '_pafw_auth_date' );
						delete_user_meta( $user_id, '_pafw_bill_key' );
						delete_user_meta( $user_id, '_pafw_card_code' );
						delete_user_meta( $user_id, '_pafw_card_name' );
						delete_user_meta( $user_id, '_pafw_card_num' );
					}
				}

				wp_safe_redirect( remove_query_arg( array ( 'user_id', 'action' ) ) );
				die();
			}
		}
	}

	return new PAFW_Admin_Users();

}

