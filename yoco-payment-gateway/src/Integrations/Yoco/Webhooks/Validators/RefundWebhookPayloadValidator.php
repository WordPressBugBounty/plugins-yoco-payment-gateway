<?php

namespace Yoco\Integrations\Yoco\Webhooks\Validators;

use Yoco\Helpers\Validation\Validator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RefundWebhookPayloadValidator extends Validator {

	protected array $rules = array(
		'type'    => 'string',
		'payload' => array(
			'currency'  => 'string',
			'paymentId' => 'string',
			'metadata'  => array(
				'checkoutId' => 'string',
			),
		),
	);
}
