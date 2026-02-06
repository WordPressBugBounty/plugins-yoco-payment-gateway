<?php

namespace Yoco\Integrations\Yoco\Webhooks\Parsers;

use Yoco\Integrations\Yoco\Webhooks\Models\WebhookPayload;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface WebhookPayloadParser {

	public function parse( array $data ): ?WebhookPayload;
}
