<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PAFW_Review' ) ) {

	class PAFW_Review {
		public static function get_default_review_contents() {
			return apply_filters( 'pafw_default_review_contents', array (
				array (
					"default" => "yes",
					"rate"    => 5,
					"label"   => __( "아주 좋아요", 'pgall-for-woocommerce' ),
					"content" => "",
				),
				array (
					"default" => "no",
					"rate"    => 4,
					"label"   => __( "맘에 들어요", 'pgall-for-woocommerce' ),
					"content" => ""
				),
				array (
					"default" => "no",
					"rate"    => 3,
					"label"   => __( "보통이에요", 'pgall-for-woocommerce' ),
					"content" => ""
				),
				array (
					"default" => "no",
					"rate"    => 2,
					"label"   => __( "그냥 그래요", 'pgall-for-woocommerce' ),
					"content" => ""
				),
				array (
					"default" => "no",
					"rate"    => 1,
					"label"   => __( "별로예요", 'pgall-for-woocommerce' ),
					"content" => ""
				),
			) );
		}

		public static function get_rate_label( $rate ) {
			$rate_options = get_option( 'pafw-smart-review-rate', array () );

			$matched = array_filter( $rate_options, function ( $rate_option ) use ( $rate ) {
				return $rate == $rate_option['rate'];
			} );

			if ( ! empty( $matched ) ) {
				$matched = current( $matched );

				return $matched['label'];
			}
		}

		public static function save_review_info( $order_id, $posted_data, $order = null ) {
			if ( $order && 'yes' == get_option( 'pafw-use-smart-review', 'no' ) ) {
				$write_review = $_REQUEST['pafw_write_smart_review'] ? 'yes' : 'no';
				$rate         = isset( $_REQUEST['pafw_smart_review_rate'] ) ? $_REQUEST['pafw_smart_review_rate'] : '';
				$content      = isset( $_REQUEST['pafw_smart_review_content'] ) ? $_REQUEST['pafw_smart_review_content'] : '';

				if ( 'yes' == $write_review ) {
					$rate_label = self::get_rate_label( $rate );

					if ( ! empty( $rate_label ) ) {
						pafw_update_meta_data( $order, '_pafw_write_smart_review', 'on' == $_REQUEST['pafw_write_smart_review'] ? 'yes' : 'no' );
						pafw_update_meta_data( $order, '_pafw_smart_review_rate', $_REQUEST['pafw_smart_review_rate'] );
						pafw_update_meta_data( $order, '_pafw_smart_review_content', $rate_label . "\n" . $_REQUEST['pafw_smart_review_content'] );
					}
				}
			}
		}
		public static function insert_comment( $order, $product, $rating ) {
			if ( $product ) {
				$user = get_user_by( 'id', pafw_get_object_property( $order, 'customer_id' ) );

				if ( $user ) {
					$author = ! empty( $user->display_name ) ? $user->display_name : $user->user_login;
				} else {
					$author = pafw_get_object_property( $order, 'billing_first_name' ) . pafw_get_object_property( $order, 'billing_last_name' );
					$author = mb_substr( $author, 0, 1 ) . str_repeat( "*", mb_strlen( $author ) - 1 );
				}

				$commentdata = array (
					'comment_post_ID'      => $product->get_parent_id() ? $product->get_parent_id() : $product->get_id(),
					'comment_author'       => $author,
					'comment_author_email' => pafw_get_object_property( $order, 'billing_email' ),
					'comment_content'      => pafw_get_meta( $order, '_pafw_smart_review_content' ),
					'comment_type'         => 'review',
					'comment_parent'       => 0,
					'comment_approved'     => 1,
					'comment_agent'        => pafw_get_object_property( $order, 'customer_user_agent' ),
					'user_id'              => pafw_get_object_property( $order, 'customer_id' ),
					'comment_author_IP'    => pafw_get_object_property( $order, 'customer_ip_address' )
				);

				$comment_id = wp_insert_comment( $commentdata );

				$rating = $rating <= 5 ? $rating : 5;
				add_comment_meta( $comment_id, 'rating', $rating, true );
				add_comment_meta( $comment_id, 'verified', 1 );
			}
		}

		public static function register_review( $order_id, $old_status, $new_status ) {
			if ( 'completed' == $new_status ) {
				$order = wc_get_order( $order_id );

				if ( 'shop_order' == $order->get_type() && 'yes' == pafw_get_meta( $order, '_pafw_write_smart_review' ) && 'yes' != pafw_get_meta( $order, '_pafw_smart_review_registered' ) ) {
					$rate = pafw_get_meta( $order, '_pafw_smart_review_rate' );
					foreach ( $order->get_items() as $item ) {
						self::insert_comment( $order, $item->get_product(), $rate );
					}

					pafw_update_meta_data( $order, '_pafw_smart_review_registered', 'yes' );
				}

			}
		}
	}

}