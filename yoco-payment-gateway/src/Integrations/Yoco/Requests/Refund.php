<?php

namespace Yoco\Integrations\Yoco\Requests;

use Error;
use WC_Order;
use WC_Order_Refund;
use Yoco\Helpers\Logger;
use function Yoco\yoco;

class Refund {

	public function refund( WC_Order $order ): ?WC_Order_Refund {
		if ( ! empty( $refunds = $order->get_refunds() ) ) {
			return array_shift( $refunds );
		}

		$args = array(
			'amount'                => $order->get_total(),
			'reason'                => __( 'Refund requested via webhook.', 'yoco_wc_payment_gateway' ),
			'order_id'              => $order->get_id(),
			'refund_payment_method' => 'class_yoco_wc_payment_gateway',
			'line_items'            => $order->get_items(),
		);

		$refund = wc_create_refund( apply_filters( 'yoco_payment_gateway/request/refund/args', $args ) );

		if ( is_wp_error( $refund ) ) {
			yoco( Logger::class )->logError( 'Refund creation failed: ' . $refund->get_error_message() . ' code: ' . $refund->get_error_code() );
			throw new Error( $refund->get_error_message(), (int) $refund->get_error_code() );
		}

		return $refund;
	}
}
