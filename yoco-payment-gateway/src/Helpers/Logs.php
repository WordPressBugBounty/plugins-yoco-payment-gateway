<?php

namespace Yoco\Helpers;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Yoco\Integrations\Yoco\Webhooks\REST\Route;
use Yoco\Integrations\Yoco\Webhooks\REST\RouteInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

		$file = sanitize_file_name( $request->get_param( 'file' ) );

		// Allow only files that start with yoco and have .log extension.
		if ( '' === $file || '.log' !== substr( $file, -4 ) || 'yoco' !== substr( $file, 0, 4 ) ) {
			return new WP_REST_Response(
				array( 'message' => 'Not found' ),
				404
			);
		}

		$base_dir = realpath( WC_LOG_DIR );
		if ( false === $base_dir ) {
			return new WP_REST_Response(
				array( 'message' => 'Server error' ),
				500
			);
		}

		$target = realpath( WC_LOG_DIR . $file );

		// realpath() resolves ../ and symlinks.
		if (
			false === $target
			|| 0 !== strpos( $target, $base_dir . DIRECTORY_SEPARATOR )
		) {
			return new WP_REST_Response(
				array( 'message' => 'Not found' ),
				404
			);
		}

		if ( ! is_file( $target ) || ! is_readable( $target ) ) {
			return new WP_REST_Response(
				array( 'message' => 'Not found' ),
				404
			);
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$log_data = file_get_contents( $target ); // NOSONAR

		add_filter(
			'rest_pre_serve_request',
			function ( $served, $result ) use ( $log_data ) {

				if (
					! $result instanceof WP_REST_Response ||
					$result->get_matched_route() !== '/yoco/logs'
				) {
					return $served;
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
