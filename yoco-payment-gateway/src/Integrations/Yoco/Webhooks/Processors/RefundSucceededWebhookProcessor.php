<?php

namespace Yoco\Integrations\Yoco\Webhooks\Processors;

use WC_Order;
use WP_REST_Response;
use Yoco\Helpers\Logger;
use Yoco\Integrations\Yoco\Requests\Refund;
use Yoco\Integrations\Yoco\Webhooks\Models\WebhookPayload;

use function Yoco\yoco;

/**
 * RefundSucceededWebhookProcessor
 */
class RefundSucceededWebhookProcessor extends WebhookProcessor {

	/**
	 * WooCommerce Order.
	 *
	 * @var WC_Order|null
	 */
	private ?WC_Order $order = null;

	/**
	 * Process refound.
	 *
	 * @param  WebhookPayload $payload Payload.
	 *
	 * @return WP_REST_Response
	 */
	public function process( WebhookPayload $payload ): WP_REST_Response {
		$this->order = $this->getOrderByCheckoutId( $payload->getCheckoutId() );
		if ( null === $this->order ) {
			return $this->sendFailResponse( 403, sprintf( 'Could not find the order for checkout id %s.', $payload->getCheckoutId() ) );
		}

		if ( 'refunded' === $this->order->get_status() ) {
			yoco( Logger::class )->logInfo( sprintf( 'Order #%s is already refunded, no need to update the order', $this->order->get_id() ) );

			return $this->sendSuccessResponse();
		}

		try {
			$request = new Refund();
			$refund  = $request->refund( $this->order, $payload );

			if ( 'completed' === $refund->get_status() ) {
				return $this->sendSuccessResponse();
			} else {
				yoco( Logger::class )->logError( sprintf( 'Failed to complete refund of order #%s - wrong order status.', $this->order->get_id() ) );

				return $this->sendFailResponse( 403, sprintf( 'Failed to complete refund of order #%s - wrong order status.', $this->order->get_id() ) );
			}
		} catch ( \Throwable $th ) {
			yoco( Logger::class )->logError( sprintf( 'Failed to complete refund of order #%s.', $this->order->get_id() ) );

			return $this->sendFailResponse( 403, sprintf( 'Failed to complete refund of order #%s.', $this->order->get_id() ) );
		}
	}
}
