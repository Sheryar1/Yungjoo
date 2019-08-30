<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'PAFW_MShop_Members' ) ) :

	class PAFW_MShop_Members {
		static function process_payment( $params, $form ) {
			try {

			} catch ( Exception $e ) {
				throw $e;
			}
		}
		public static function submit_action( $actions ) {
			$actions['pafw_payment'] = __( '결제하기', 'pgall-for-woocommerce' );

			return $actions;
		}
		public static function add_form_classes( $classes, $form ) {
			if ( 'pafw_payment' == $form->get_submit_action() ) {
				$classes[] = 'checkout';
			}

			return $classes;
		}
		public static function output_unique_id( $form ) {
			?>
            <input type="hidden" name="_pafw_uid" value="<?php echo uniqid( 'pafw_' ); ?>">
			<?php
		}

		public static function add_field_rules( $field_rules, $form_data ) {

			foreach ( $form_data as $element ) {

				if ( 'Product' == $element['type'] && 'custom' == $element['property']['type'] && 'yes' == $element['property']['show_to_user'] ) {
					$field_rules[ 'order_title' ] = array (
						'rules' => array (
							array (
								'type'   => 'empty',
								'prompt' => __( '결제 내용을 입력하세요.', 'pgall-for-woocommerce' )
							)
						)
					);
					$field_rules[ 'order_amount' ] = array (
						'rules' => array (
							array (
								'type'   => 'empty',
								'prompt' => __( '결제 금액을 입력하세요.', 'pgall-for-woocommerce' )
							)
						)
					);
				}
			}

		    return $field_rules;
        }
	}

endif;

