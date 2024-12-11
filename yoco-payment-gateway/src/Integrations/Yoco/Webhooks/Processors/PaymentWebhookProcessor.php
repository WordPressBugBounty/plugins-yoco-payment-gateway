<?php

namespace Yoco\Integrations\Yoco\Webhooks\Processors;

use WC_Order;
use WP_REST_Response;
use Yoco\Gateway\Metadata;
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
		set_transient( 'yoco_webhook_processing', true, 10 );
		$this->order = $this->getOrderByCheckoutId( $payload->getCheckoutId() );
		if ( null === $this->order ) {
			return $this->sendFailResponse( 404, sprintf( 'No order found for CheckoutId %s.', $payload->getCheckoutId() ) );
		}

		if ( get_transient( 'yoco_order_processing_' . $this->order->get_id() ) ) {
			return $this->sendFailResponse( 409, sprintf( 'Order #%s processing already started.', $this->order->get_id() ) );
		}

		if ( $payload->getPaymentId() === yoco( Metadata::class )->getOrderPaymentId( $this->order ) ) {
			delete_transient( 'yoco_order_processing_' . $this->order->get_id() );
			return $this->sendSuccessResponse();
		}

		if (
			$this->order->get_transaction_id() !== $payload->getPaymentId()
			&& true === $this->order->payment_complete( $payload->getPaymentId() )
		) {
			/**
			* Fires an action hook after a Yoco payment has been completed for an order.
			*
			* @param WC_Order $order   The order object.
			* @param string   $payload Yoco Payment data.
			*
			* @since 1.0.0
			*/
			do_action( 'yoco_payment_gateway/payment/completed', $this->order, $payload );

			delete_transient( 'yoco_order_processing_' . $this->order->get_id() );
			return $this->sendSuccessResponse();
		} else {
			yoco( Logger::class )->logError( sprintf( 'Failed to complete payment of order #%s.', $this->order->get_id() ) );

			return $this->sendFailResponse( 409, sprintf( 'Failed to complete payment of order #%s.', $this->order->get_id() ) );
		}
	}
}
