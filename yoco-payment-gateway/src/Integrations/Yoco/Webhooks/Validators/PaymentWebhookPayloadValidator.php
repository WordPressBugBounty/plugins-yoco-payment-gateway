<?php

namespace Yoco\Integrations\Yoco\Webhooks\Validators;

use Yoco\Helpers\Validation\Validator;

class PaymentWebhookPayloadValidator extends Validator {

	protected array $rules = array(
		'type'    => 'string',
		'payload' => array(
			'id'       => 'string',
			'currency' => 'string',
			'metadata' => array(
				'checkoutId' => 'string',
			),
		),
	);
}
