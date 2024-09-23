<?php

namespace Yoco\Integrations\Yoco\Webhooks\REST;

class Rewrites {

	public function __construct() {
		add_action( 'init', array( $this, 'rewriteWebhookEndpoint' ) );
	}

	public function rewriteWebhookEndpoint(): void {
		add_rewrite_rule( 'yoco/webhook/?$', 'index.php?rest_route=/yoco/webhook', 'top' );
		add_rewrite_rule( 'yoco/logs/?$', 'index.php?rest_route=/yoco/logs', 'top' );
	}
}
