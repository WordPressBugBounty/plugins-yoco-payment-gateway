<?php

namespace Yoco\Helpers;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Yoco\Integrations\Yoco\Webhooks\REST\Route;
use Yoco\Integrations\Yoco\Webhooks\REST\RouteInterface;

class Logs extends Route implements RouteInterface {

	private string $path = 'logs';

	public function register(): bool {
		$args = array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'callback' ),
			'permission_callback' => array( $this, 'permit' ),
		);

		return register_rest_route( $this->namespace, $this->path, $args, true );
	}

	public function callback( WP_REST_Request $request ): WP_REST_Response {

		if (
			false === strpos( $request->get_param( 'file' ), 'yoco' )
			|| ! file_exists( WC_LOG_DIR . $request->get_param( 'file' ) )
		) {
			return new WP_REST_Response(
				array( 'message' => 'Not found' ),
				404
			);
		}

		$log_data = file_get_contents( WC_LOG_DIR . $request->get_param( 'file' ) ); //NOSONAR

		add_filter(
			'rest_pre_serve_request',
			function( $bool, $result ) use ( $log_data ) {

				if ( '/yoco/logs' !== $result->get_matched_route() ) {
					return $bool;
				}

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $log_data;

				return true;
			},
			10,
			2
		);

		return new WP_REST_Response( $log_data, 200 );
	}

	public function permit( WP_REST_Request $request ): bool {
		return true;
	}
}
