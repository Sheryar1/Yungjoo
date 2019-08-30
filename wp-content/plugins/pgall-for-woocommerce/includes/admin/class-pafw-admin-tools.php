<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Admin_Tools' ) ) :

	class PAFW_Admin_Tools {

		public static function do_migrate() {
			self::migrate_user_data();
			self::migrate_subscription_data();
			self::migrate_order_data();
		}

		protected static function get_user_data() {
			global $wpdb;

			return $wpdb->get_col( "
                SELECT DISTINCT user_id 
                FROM `{$wpdb->usermeta}` 
                WHERE 
                  meta_key = 'msspg_pg' 
                  AND meta_value = 'nicepay-subscription';
                " );
		}

		protected static function get_subscription_data() {
			global $wpdb;

			return $wpdb->get_col( "
                SELECT post.ID 
                FROM {$wpdb->posts} post 
                LEFT JOIN {$wpdb->postmeta} postmeta ON post.ID = postmeta.post_id AND postmeta.meta_key = '_payment_method'
                WHERE
                    post.post_type = 'shop_subscription'
                    AND postmeta.meta_value = 'nicepay-subscription'
                " );
		}

		protected static function get_order_data() {
			global $wpdb;

			return $wpdb->get_col( "
                SELECT post.ID 
                FROM {$wpdb->posts} post 
                LEFT JOIN {$wpdb->postmeta} postmeta ON post.ID = postmeta.post_id AND postmeta.meta_key = '_payment_method'
                WHERE
                    post.post_type = 'shop_order' 
                    AND postmeta.meta_value = 'nicepay-subscription'
                " );
		}
		protected static function migrate_user_data() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				die( - 1 );
			}

			$users = self::get_user_data();

			if ( ! empty( $users ) ) {
				$meta_key = array (
					'msspg_authdate' => '_pafw_auth_date',
					'msspg_billkey'  => '_pafw_bill_key',
					'msspg_cardcode' => '_pafw_card_code',
					'msspg_cardname' => '_pafw_card_name',
					'msspg_cardno'   => '_pafw_card_num',
				);

				foreach ( $users as $user_id ) {
					update_user_meta( $user_id, '_pafw_payment_method', 'nicepay_subscription' );
					update_user_meta( $user_id, 'msspg_pg', 'nicepay_subscription' );
					foreach ( $meta_key as $old => $new ) {
						$old_value = get_user_meta( $user_id, $old, true );
						if ( ! empty( $old_value ) ) {
							update_user_meta( $user_id, $new, $old_value );
						}
					}
				}
			}
		}

		protected static function migrate_subscription_data() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				die( - 1 );
			}

			$subscriptions = self::get_subscription_data();

			if ( ! empty( $subscriptions ) ) {
				$meta_key = array (
					'msspg_authdate' => '_pafw_auth_date',
					'msspg_billkey'  => '_pafw_bill_key',
					'msspg_cardcode' => '_pafw_card_code',
					'msspg_cardname' => '_pafw_card_name',
					'msspg_cardno'   => '_pafw_card_num',
				);

				foreach ( $subscriptions as $subscription_id ) {
					update_post_meta( $subscription_id, '_payment_method', 'nicepay_subscription' );
					foreach ( $meta_key as $old => $new ) {
						$old_value = get_post_meta( $subscription_id, '_' . $old, true );
						if ( ! empty( $old_value ) ) {
							update_post_meta( $subscription_id, $new, $old_value );
						}
					}
				}
			}
		}

		public static function migrate_order_data() {

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				die( - 1 );
			}

			$orders = self::get_order_data();

			if ( ! empty( $orders ) ) {
				$meta_key = array (
					'_msspg_tid'        => array ( '_pafw_txnid', '_transaction_id' ),
					'_msspg_is_renewal' => '_pafw_bill_key',
					'_msspg_authdate'   => '_pafw_auth_date',
					'_msspg_cardcode'   => '_pafw_card_code',
					'_msspg_cardname'   => '_pafw_card_name',
					'_msspg_cardno'     => '_pafw_card_num',
					'_msspg_cardquota'  => '_pafw_card_quota',
				);

				foreach ( $orders as $order_id ) {
					update_post_meta( $order_id, '_payment_method', 'nicepay_subscription' );
					update_post_meta( $order_id, '_pafw_payment_method', 'nicepay_subscription' );
					foreach ( $meta_key as $old => $new ) {
						$old_value = get_post_meta( $order_id, $old, true );
						if ( ! empty( $old_value ) ) {
							if ( is_array( $new ) ) {
								foreach ( $new as $new_key ) {
									update_post_meta( $order_id, $new_key, $old_value );
								}
							} else {
								update_post_meta( $order_id, $new, $old_value );
							}
						}
					}
				}
			}
		}

		static function get_migration_info() {
			$info = array ();

			$users = self::get_user_data();

			if ( count( $users ) ) {
				$info[] = sprintf( __( '사용자 카드 정보 : %d 건', 'pgall-for-woocommerce' ), count( $users ) );
			}

			$subscriptions = self::get_subscription_data();

			if ( count( $subscriptions ) ) {
				$info[] = sprintf( __( '정기결제권 : %d 건', 'pgall-for-woocommerce' ), count( $subscriptions ) );
			}

			$orders = self::get_order_data();

			if ( count( $orders ) > 0 ) {
				$info[] = sprintf( __( '주문 : %d 건', 'pgall-for-woocommerce' ), count( $orders ) );
			}

			return implode( '<br>', $info );
		}

		static function get_setting() {
			$info = self::get_migration_info();

			if ( ! empty( $info ) ) {
				return array (
					'type'     => 'Page',
					'class'    => 'active',
					'title'    => __( '기본 설정', 'pgall-for-woocommerce' ),
					'elements' => array (
						array (
							'type'     => 'Section',
							'title'    => __( 'PGALL 도구', 'pgall-for-woocommerce' ),
							'elements' => array (
								array (
									'id'             => 'pafw_migration',
									'title'          => '정기결제권 마이그레이션',
									'label'          => '실행',
									'iconClass'      => 'icon settings',
									'className'      => '',
									'type'           => 'Button',
									'default'        => '',
									'actionType'     => 'ajax',
									'confirmMessage' => __( '엠샵 정기결제 플러그인의 정기결제권 정보를 마이그레이션 하시겠습니까? ', 'pgall-for-woocommerce' ),
									'ajaxurl'        => admin_url( 'admin-ajax.php' ),
									'action'         => PAFW()->slug() . '-migration_subscription',
									"desc2"          => "<div class='desc2' style='padding-left: 10px;'><span style='color:red'>[주의] 반드시 데이터 백업 후 마이그레이션을 진행하시기 바랍니다.</span><br>" . $info . '</div>'
								),
							)
						)
					)
				);
			} else {
				return array (
					'type'     => 'Page',
					'class'    => 'active',
					'title'    => __( '기본 설정', 'pgall-for-woocommerce' ),
					'elements' => array (
						array (
							'type'     => 'Section',
							'title'    => __( 'PGALL 도구', 'pgall-for-woocommerce' ),
							'elements' => array (
								array (
									'id'        => 'pafw_migration',
									'title'     => __( '정기결제권 마이그레이션', 'pgall-for-woocommerce' ),
									'className' => '',
									'type'      => 'Label',
									'readonly'  => 'yes',
									'desc2'     => __( '<div class="desc2">마이그레이션이 필요한 정기결제권 정보가 없습니다.</div>', 'pgall-for-woocommerce' ),
								)
							)
						)
					)
				);
			}
		}

		static function enqueue_scripts() {
			wp_enqueue_style( 'mshop-setting-manager', PAFW()->plugin_url() . '/includes/admin/setting-manager/css/setting-manager.min.css' );
			wp_enqueue_script( 'mshop-setting-manager', PAFW()->plugin_url() . '/includes/admin/setting-manager/js/setting-manager.min.js', array (
				'underscore',
				'jquery',
				'jquery-ui-core'
			) );
		}

		public static function output() {
			$settings = self::get_setting();

			self::enqueue_scripts();

			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array (
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => PAFW()->slug() . '-update_pafw_settings',
				'settings' => $settings,
				'slug'     => PAFW()->slug()
			) );

			?>
            <script>
                jQuery(document).ready(function () {
                    jQuery(this).trigger('mshop-setting-manager', ['mshop-setting-wrapper', '100', <?php echo json_encode( PAFW_SettingHelper::get_settings( $settings ) ); ?>, null, null])
                })
            </script>

            <div id="mshop-setting-wrapper"></div>
			<?php
		}

	}
endif;



