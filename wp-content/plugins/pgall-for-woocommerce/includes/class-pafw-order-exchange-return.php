<?php



if ( ! defined( 'ABSPATH' ) ){
    exit;
}

if ( ! class_exists( 'PAFW_Order_Exchange_Return' ) ) {

	class PAFW_Order_Exchange_Return extends WC_Order {
		public $order_type = 'pafw_ex';
		public $date;
		public $reason;
		protected function init( $order ) {
			if ( is_numeric( $order ) ) {
				$this->id   = absint( $order );
				$this->post = get_post( $order );
				$this->get_exchange_return( $this->id );
			} elseif ( $order instanceof WC_Order_Refund ) {
				$this->id   = absint( $order->id );
				$this->post = $order->post;
				$this->get_exchange_return( $this->id );
			} elseif ( isset( $order->ID ) ) {
				$this->id   = absint( $order->ID );
				$this->post = $order;
				$this->get_exchange_return( $this->id );
			}
		}
		public function get_exchange_return( $id = 0 ) {
			if ( ! $id ) {
				return false;
			}

			if ( $result = get_post( $id ) ) {
				$this->populate( $result );

				return true;
			}

			return false;
		}
		public function populate( $result ) {
			// Standard post data
			$this->id            = $result->ID;
			$this->date          = $result->post_date;
			$this->modified_date = $result->post_modified;
			$this->reason        = $result->post_excerpt;
		}

		public function get_type() {
			return apply_filters( 'pafw_exchange_return_type', pafw_get_meta( $this, '_type' ), $this );
		}

		public function is_exchange() {
			return 'exchange' == $this->get_type();
		}

		public function is_return() {
			return 'return' == $this->get_type();
		}

		public function get_reason() {
			return apply_filters( 'pafw_exchange_return_reason', pafw_get_meta( $this, '_reason' ), $this );
		}
	}

}