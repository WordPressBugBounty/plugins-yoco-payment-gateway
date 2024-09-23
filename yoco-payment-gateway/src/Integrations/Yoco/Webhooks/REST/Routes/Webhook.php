<?php

namespace Yoco\Integrations\Yoco\Webhooks\REST\Routes;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Yoco\Integrations\Webhook\Guard;
use Yoco\Integrations\Yoco\Webhooks\Controllers\WebhookController;
use Yoco\Integrations\Yoco\Webhooks\REST\Route;
use Yoco\Integrations\Yoco\Webhooks\REST\RouteInterface;

use function Yoco\yoco;

class Webhook extends Route implements RouteInterface {

	private string $path = 'webhook';

	public function register(): bool {
		$args = array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'callback' ),
			'permission_callback' => array( $this, 'permit' ),
		);

		return register_rest_route( $this->namespace, $this->path, $args, true );
	}

	public function callback( WP_REST_Request $request ): WP_REST_Response {
		return wp_salt() === json_decode( $request->get_body() )
			? new WP_REST_Response( 'OK', 200 )
			: ( new WebhookController( $request ) )->handleRequest();
	}

	public function permit( WP_REST_Request $request ): bool {
		return wp_salt() === json_decode( $request->get_body() ) ? true : yoco( Guard::class )->verifySignature( $request );
	}
}
