<?php

namespace Yoco\Integrations\Yoco\Webhooks\REST;

use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RouteInterface {

	public function register(): bool;

	public function callback( WP_REST_Request $request): WP_REST_Response;

	public function permit( WP_REST_Request $request): bool;
}
