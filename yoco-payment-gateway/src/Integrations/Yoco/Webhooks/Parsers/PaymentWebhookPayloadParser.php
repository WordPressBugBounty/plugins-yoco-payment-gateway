<?php

namespace Yoco\Integrations\Yoco\Webhooks\Parsers;

use Error;
use Yoco\Helpers\Logger;
use Yoco\Integrations\Yoco\Webhooks\Models\WebhookPayload;
use Yoco\Integrations\Yoco\Webhooks\Validators\PaymentWebhookPayloadValidator;

use function Yoco\yoco;

class PaymentWebhookPayloadParser implements WebhookPayloadParser {

	protected ?WebhookPayload $payload = null;

	public function __construct() {
		$this->payload = new WebhookPayload();
	}

	public function parse( array $data ): ?WebhookPayload {
		$this->validate( $data );

		$this->payload->setCurrency( $data['payload']['currency'] );
		$this->payload->setEventType( $data['type'] );
		$this->payload->setCheckoutId( $data['payload']['metadata']['checkoutId'] );
		$this->payload->setPaymentId( $data['payload']['id'] );

		return $this->payload;
	}

	private function validate( array $data ): void {
		$validator = new PaymentWebhookPayloadValidator();
		$validator->validate( $data );

		if ( $validator->getErrorBag()->hasErrors() ) {
			$errorsString = join( ', ', $validator->getErrorBag()->getErrors() );
			$errorMessage = sprintf( __( 'Webhook request body is invalid. Violated fields: %s.', 'yoco_wc_payment_gateway' ), $errorsString );

			yoco( Logger::class )->logError( $errorMessage );

			throw new Error( $errorMessage );
		}
	}
}
