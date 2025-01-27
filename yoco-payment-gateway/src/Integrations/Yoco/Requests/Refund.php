<?php

namespace Yoco\Integrations\Yoco\Requests;

use Error;
use WC_Order;
use WC_Order_Refund;
use Yoco\Gateway\Metadata;
use Yoco\Helpers\Logger;
use Yoco\Helpers\MoneyFormatter as Money;
use Yoco\Integrations\Yoco\Webhooks\Models\WebhookPayload;
use function Yoco\yoco;

class Refund {

	public function refund( WC_Order $order, WebhookPayload $payload ): ?WC_Order_Refund {
		$refunds = $order->get_refunds();
		$data    = array(
			'refundId' => $payload->getId(),
		);

		if ( is_array( $refunds ) ) {
			/**
			 * WC_Order_refund.
			 *
			 * @var WC_Order_refund $refund
			 */
			foreach ( $refunds as $refund ) {
				$refund_id = yoco( Metadata::class )->getOrderRefundId( $refund );

				if ( $refund_id === $payload->getId() ) {
					return $refund;
				}

				$amount = yoco( Money::class )->format( $refund->get_amount() );

				if ( '' === $refund_id && $amount === $payload->getAmount() ) {
					do_action( 'yoco_payment_gateway/order/refunded', $refund, $data );
					do_action( 'yoco_payment_gateway/order/refunded', $order, $data );

					return $refund;
				}
			}
		}

		$args = array(
			'amount'         => $payload->getAmount() / 100,
			'reason'         => sprintf(
				__( 'Refund requested via Yoco Portal. Refund ID (%s)', 'yoco_wc_payment_gateway' ),
				$payload->getId()
			),
			'order_id'       => $order->get_id(),
			'refund_payment' => false,
		);

		$refund = wc_create_refund( apply_filters( 'yoco_payment_gateway/request/refund/args', $args ) );

		if ( is_wp_error( $refund ) ) {
			yoco( Logger::class )->logError( 'Refund creation failed: ' . $refund->get_error_message() . ' code: ' . $refund->get_error_code() );
			throw new Error( $refund->get_error_message(), (int) $refund->get_error_code() );
		}

		do_action( 'yoco_payment_gateway/order/refunded', $refund, $data );
		do_action( 'yoco_payment_gateway/order/refunded', $order, $data );

		return $refund;
	}
}
