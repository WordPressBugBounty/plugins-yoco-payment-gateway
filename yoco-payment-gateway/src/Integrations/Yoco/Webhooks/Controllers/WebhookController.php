<?php

namespace Yoco\Integrations\Yoco\Webhooks\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use Yoco\Helpers\Logger;
use Yoco\Integrations\Yoco\Webhooks\Events\WebhookEventsManager;

use function Yoco\yoco;

class WebhookController {

	private ?WP_REST_Request $request = null;

	public function __construct( WP_REST_Request $request ) {
		$this->request = $request;
	}

	public function handleRequest(): WP_REST_Response {
		$method = $this->request->get_method();

		if ( 'GET' === $method ) {
			$this->handleGetRequest();
		}

		if ( 'POST' !== $method ) {
			return $this->handleUnallowedRequests();
		}

		return $this->handlePostRequest();
	}

	public function handleGetRequest() {
		wp_safe_redirect( home_url( '/' ), 302, 'Yoco: WebhookController: handle GET' );
		exit;
	}

	public function handlePostRequest(): WP_REST_Response {
		$body      = (array) json_decode( $this->request->get_body(), true );
		$eventType = isset( $body['type'] ) && ! empty( $body['type'] ) ? $body['type'] : '';

		try {
			$events_manager = yoco( WebhookEventsManager::class );
			$payload        = $events_manager->getEventParser( $eventType )->parse( $body );
			$processor      = $events_manager->getEventProcessor( $eventType );

			return $processor->process( $payload );
		} catch ( \Throwable $th ) {
			yoco( Logger::class )->logError( sprintf( 'Failed to handle webhook post request. %s', $th->getMessage() ) );

			return new WP_REST_Response(
				array(
					'message' => $th->getMessage(),
				),
				400
			);
		}
	}

	public function handleUnallowedRequests(): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'message' => __( 'Method not allowed', 'yoco_wc_payment_gateway' ),
			),
			405
		);
	}
}
