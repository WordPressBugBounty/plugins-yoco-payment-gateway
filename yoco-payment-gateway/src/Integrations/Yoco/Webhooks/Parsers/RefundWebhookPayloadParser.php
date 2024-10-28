<?php

namespace Yoco\Integrations\Yoco\Webhooks\Parsers;

use Error;
use Yoco\Helpers\Logger;
use Yoco\Integrations\Yoco\Webhooks\Models\WebhookPayload;
use Yoco\Integrations\Yoco\Webhooks\Validators\RefundWebhookPayloadValidator;

use function Yoco\yoco;

class RefundWebhookPayloadParser implements WebhookPayloadParser {

	protected ?WebhookPayload $payload = null;

	public function __construct() {
		$this->payload = new WebhookPayload();
	}

	public function parse( array $data ): ?WebhookPayload {
		$this->validate( $data );

		$this->payload->setCurrency( $data['payload']['currency'] );
		$this->payload->setEventType( $data['type'] );
		$this->payload->setCheckoutId( $data['payload']['metadata']['checkoutId'] );
		$this->payload->setPaymentId( $data['payload']['paymentId'] );
		$this->payload->setId( $data['payload']['id'] );
		$this->payload->setStatus( $data['payload']['status'] );
		$this->payload->setAmount( $data['payload']['amount'] );

		if ( isset( $data['payload']['refundableAmount'] ) && ! empty( $data['payload']['refundableAmount'] ) ) {
			$this->payload->setRefundableAmount( $data['payload']['refundableAmount'] );
		}

		if ( isset( $data['payload']['failureReason'] ) && ! empty( $data['payload']['failureReason'] ) ) {
			$this->payload->setFailureReason( $data['payload']['failureReason'] );
		}

		return $this->payload;
	}

	private function validate( array $data ): void {
		$validator = new RefundWebhookPayloadValidator();
		$validator->validate( $data );

		if ( $validator->getErrorBag()->hasErrors() ) {
			$errorsString = join( ', ', $validator->getErrorBag()->getErrors() );
			$errorMessage = sprintf( __( 'Webhook request body is invalid. Violated fields: %s.', 'yoco_wc_payment_gateway' ), $errorsString );

			yoco( Logger::class )->logError( $errorMessage );

			throw new Error( $errorMessage );
		}
	}
}
