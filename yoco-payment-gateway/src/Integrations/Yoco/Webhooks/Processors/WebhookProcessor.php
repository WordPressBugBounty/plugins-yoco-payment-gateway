<?php

namespace Yoco\Integrations\Yoco\Webhooks\Processors;

use WC_Order;
use WP_REST_Response;
use Yoco\Repositories\OrdersRepository;

/**
 * WebhookProcessor
 */
abstract class WebhookProcessor {

	/**
	 * Send success response.
	 *
	 * @return WP_REST_Response
	 */
	protected function sendSuccessResponse(): WP_REST_Response {
		return new WP_REST_Response();
	}

	/**
	 * Send fail response.
	 *
	 * @param  int    $status HTTP status code.
	 * @param  string $description Message.
	 *
	 * @return WP_REST_Response
	 */
	protected function sendFailResponse( int $status, string $description = '' ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'description' => $description,
			),
			$status
		);
	}

	/**
	 * Get order by checkout ID.
	 *
	 * @param  string $checkoutId Yoco checkout ID.
	 *
	 * @return WC_Order|null
	 */
	protected function getOrderByCheckoutId( string $checkoutId ): ?WC_Order {
		return OrdersRepository::getByYocoCheckoutId( $checkoutId );
	}
}
