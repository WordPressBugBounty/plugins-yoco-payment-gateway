<?php

namespace Yoco\Gateway\Checkout;

use WC_Order;

class Method {

	public function __construct() {
		add_action( 'woocommerce_new_order', array( $this, 'setMethod' ) );
		add_action( 'woocommerce_after_order_object_save', array( $this, 'updateMethod' ) );
	}

	public function setMethod( int $orderId ): void {
		$order = new WC_Order( $orderId );

		if ( 'class_yoco_wc_payment_gateway' !== $order->get_payment_method() ) {
			return;
		}

		$title = __( 'Yoco', 'yoco_wc_payment_gateway' );

		if ( $title !== $order->get_payment_method_title() ) {
			$order->set_payment_method_title( $title );
			$order->set_created_via( 'checkout' );
			$order->save();
		}
	}

	public function updateMethod( WC_Order $order ): void {
		if ( 'class_yoco_wc_payment_gateway' !== $order->get_payment_method() ) {
			return;
		}

		$title = __( 'Yoco', 'yoco_wc_payment_gateway' );

		if ( $title !== $order->get_payment_method_title() ) {
			$order->set_payment_method_title( $title );
			$order->set_created_via( 'checkout' );
			$order->save();
		}
	}
}
