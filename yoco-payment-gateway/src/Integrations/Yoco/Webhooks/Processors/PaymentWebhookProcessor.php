<?php

namespace Yoco\Integrations\Yoco\Webhooks\Processors;

use WC_Order;
use WP_REST_Response;
use Yoco\Helpers\Logger;
use Yoco\Integrations\Yoco\Webhooks\Models\WebhookPayload;

use function Yoco\yoco;

/**
 * PaymentWebhookProcessor
 */
class PaymentWebhookProcessor extends WebhookProcessor {

	/**
	 * WooCommerce Order.
	 *
	 * @var WC_Order|null
	 */
	private ?WC_Order $order = null;

	/**
	 * Process payment.
	 *
	 * @param  WebhookPayload $payload Payload.
	 *
	 * @return WP_REST_Response
	 */
	public function process( WebhookPayload $payload ): WP_REST_Response {
		update_option( 'yoco_webhook', current_time( 'mysql' ) );
		$this->order = $this->getOrderByCheckoutId( $payload->getCheckoutId() );
		if ( null === $this->order ) {
			return $this->sendFailResponse( 404, sprintf( 'No order found for CheckoutId %s.', $payload->getCheckoutId() ) );
		}

		if ( ! empty( $this->order->get_meta( 'yoco_order_payment_id', true, 'yoco' ) ) ) {
			return $this->sendSuccessResponse();
		}

		if ( true === $this->order->update_status( 'processing' ) ) {
			do_action( 'yoco_payment_gateway/payment/completed', $this->order, $payload );

			return $this->sendSuccessResponse();
		} else {
			yoco( Logger::class )->logError( sprintf( 'Failed to complete payment of order #%s.', $this->order->get_id() ) );

			return $this->sendFailResponse( 500, sprintf( 'Failed to complete payment of order #%s.', $this->order->get_id() ) );
		}
	}
}
