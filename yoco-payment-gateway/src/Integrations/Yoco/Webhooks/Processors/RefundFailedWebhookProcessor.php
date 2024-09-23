<?php

namespace Yoco\Integrations\Yoco\Webhooks\Processors;

use WC_Order;
use WP_REST_Response;
use Yoco\Gateway\Notes;
use Yoco\Integrations\Yoco\Webhooks\Models\WebhookPayload;

use function Yoco\yoco;

/**
 * RefundFailedWebhookProcessor
 */
class RefundFailedWebhookProcessor extends WebhookProcessor {

	/**
	 * WooCommerce Order.
	 *
	 * @var WC_Order|null
	 */
	private ?WC_Order $order = null;

	/**
	 * Process refund.
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
			return $this->sendFailResponse( 403, sprintf( 'Order for checkout id %s is already refunded.', $payload->getCheckoutId() ) );
		}

		yoco( Notes::class )->addNote(
			$this->order,
			$payload->hasFailureReason()
			// translators: message.
			? sprintf( __( 'Yoco: %s', 'yoco_wc_payment_gateway' ), $payload->getFailureReason() )
			: __( 'Yoco: Failed to refund the order.', 'yoco_wc_payment_gateway' )
		);

		return $this->sendSuccessResponse();
	}
}
