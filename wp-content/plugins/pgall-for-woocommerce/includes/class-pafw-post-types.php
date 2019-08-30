<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class PAFW_Post_Type {
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_order_types' ), 10 );
		add_filter( 'woocommerce_register_shop_order_post_statuses', array( __CLASS__, 'register_order_status' ) );
	}
	public static function register_order_types() {
		wc_register_order_type(
			'shop_order_pafw_ex',
			apply_filters( 'woocommerce_register_post_type_shop_order_exchange',
				array(
					'label'                            => __( 'Exchange or Return', 'pgall-for-woocommerce' ),
					'capability_type'                  => 'shop_order',
					'public'                           => false,
					'hierarchical'                     => false,
					'supports'                         => false,
					'exclude_from_orders_screen'       => false,
					'add_order_meta_boxes'             => false,
					'exclude_from_order_count'         => true,
					'exclude_from_order_views'         => true,
					'exclude_from_order_reports'       => false,
					'exclude_from_order_sales_reports' => true,
					'class_name'                       => 'PAFW_Order_Exchange_Return'
				)
			)
		);
	}
	public static function register_order_status( $order_statuses ) {

		$order_statuses = array_merge( $order_statuses,
			array(
				'wc-shipped'    => array(
					'label'                     => _x( '배송완료', 'Order status', 'pgall-for-woocommerce' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( '배송완료 <span class="count">(%s)</span>', '배송완료 <span class="count">(%s)</span>', 'pgall-for-woocommerce' )
				),
				'wc-cancel-request' => array (
					'label'                     => _x( '주문취소요청', 'Order status', 'pgall-for-woocommerce' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( '주문취소요청 <span class="count">(%s)</span>', '주문취소요청 <span class="count">(%s)</span>', 'pgall-for-woocommerce' )
				),
				'wc-exchange-request'    => array(
					'label'                     => _x( '교환신청', 'Order status', 'pgall-for-woocommerce' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( '교환신청 <span class="count">(%s)</span>', '교환신청 <span class="count">(%s)</span>', 'pgall-for-woocommerce' )
				),
				'wc-return-request' => array(
					'label'                     => _x( '반품신청', 'Order status', 'pgall-for-woocommerce' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( '반품신청 <span class="count">(%s)</span>', '반품신청 <span class="count">(%s)</span>', 'pgall-for-woocommerce' )
				),
				'wc-accept-exchange'    => array(
					'label'                     => _x( '교환접수', 'Order status', 'pgall-for-woocommerce' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( '교환접수 <span class="count">(%s)</span>', '교환접수 <span class="count">(%s)</span>', 'pgall-for-woocommerce' )
				),
				'wc-accept-return' => array(
					'label'                     => _x( '반품접수', 'Order status', 'pgall-for-woocommerce' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( '반품접수 <span class="count">(%s)</span>', '반품접수 <span class="count">(%s)</span>', 'pgall-for-woocommerce' )
				)
			)
		);

		return $order_statuses;
	}

}

PAFW_Post_Type::init();
