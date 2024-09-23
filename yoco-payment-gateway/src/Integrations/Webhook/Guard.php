<?php

namespace Yoco\Integrations\Webhook;

use WP_REST_Request;

class Guard {

	public function verifySignature( WP_REST_Request $request ): bool {
		$validator = new SignatureValidator();

		$headers = array(
			'webhook_id'        => $request->get_header( 'webhook_id' ),
			'webhook_timestamp' => $request->get_header( 'webhook_timestamp' ),
			'webhook_signature' => $request->get_header( 'webhook_signature' ),
		);

		return $validator->validate( $request->get_body(), $headers );
	}
}
