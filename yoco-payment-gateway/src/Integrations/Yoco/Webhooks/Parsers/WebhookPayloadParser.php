<?php

namespace Yoco\Integrations\Yoco\Webhooks\Parsers;

use Yoco\Integrations\Yoco\Webhooks\Models\WebhookPayload;

interface WebhookPayloadParser {

	public function parse( array $data): ?WebhookPayload;
}
