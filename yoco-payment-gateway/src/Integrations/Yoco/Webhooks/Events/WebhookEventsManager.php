<?php

namespace Yoco\Integrations\Yoco\Webhooks\Events;

use Error;
use Yoco\Integrations\Yoco\Webhooks\Parsers\PaymentWebhookPayloadParser;
use Yoco\Integrations\Yoco\Webhooks\Parsers\RefundWebhookPayloadParser;
use Yoco\Integrations\Yoco\Webhooks\Parsers\WebhookPayloadParser;
use Yoco\Integrations\Yoco\Webhooks\Processors\PaymentWebhookProcessor;
use Yoco\Integrations\Yoco\Webhooks\Processors\RefundFailedWebhookProcessor;
use Yoco\Integrations\Yoco\Webhooks\Processors\RefundSucceededWebhookProcessor;
use Yoco\Integrations\Yoco\Webhooks\Processors\WebhookProcessor;
use Yoco\Helpers\Logger;
use function Yoco\yoco;

class WebhookEventsManager {

	private array $eventsProcessors = array();
	private array $eventsParsers    = array();

	public function __construct() {
		$this->eventsProcessors = array(
			'payment.succeeded' => PaymentWebhookProcessor::class,
			'refund.succeeded'  => RefundSucceededWebhookProcessor::class,
			'refund.failed'     => RefundFailedWebhookProcessor::class,
		);

		$this->eventsParsers = array(
			'payment.succeeded' => PaymentWebhookPayloadParser::class,
			'refund.succeeded'  => RefundWebhookPayloadParser::class,
			'refund.failed'     => RefundWebhookPayloadParser::class,
		);
	}

	public function getEvents(): array {
		return array_keys( $this->eventsProcessors );
	}

	public function getEventsProcessors(): array {
		return $this->eventsProcessors;
	}

	public function getEventsParsers(): array {
		return $this->eventsParsers;
	}

	public function getEventProcessor( string $eventType ): WebhookProcessor {
		// TODO: CP: Confirm whether we should throw an error if we do not recognise the event type?
		if ( ! array_key_exists( $eventType, $this->eventsProcessors ) ) {
			yoco( Logger::class )->logError( sprintf( 'Unknown event type to process: %s.', $eventType ) );
			throw new Error( sprintf( __( 'Unknown event type to process: %s.', 'yoco_wc_payment_gateway' ), $eventType ) );
		}

		return new $this->eventsProcessors[ $eventType ]();
	}

	public function getEventParser( string $eventType ): WebhookPayloadParser {
		// TODO: CP: Confirm whether we should throw an error if we do not recognise the event type?
		if ( ! array_key_exists( $eventType, $this->eventsParsers ) ) {
			yoco( Logger::class )->logError( sprintf( 'Unknown event type to parse: %s.', $eventType ) );
			throw new Error( sprintf( __( 'Unknown event type to parse: %s.', 'yoco_wc_payment_gateway' ), $eventType ) );
		}

		return new $this->eventsParsers[ $eventType ]();
	}
}
