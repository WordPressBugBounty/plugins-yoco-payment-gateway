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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WebhookEventsManager {

	private array $events_processors = array();
	private array $events_parsers    = array();

	public function __construct() {
		$this->events_processors = array(
			'payment.succeeded' => PaymentWebhookProcessor::class,
			'refund.succeeded'  => RefundSucceededWebhookProcessor::class,
			'refund.failed'     => RefundFailedWebhookProcessor::class,
		);

		$this->events_parsers = array(
			'payment.succeeded' => PaymentWebhookPayloadParser::class,
			'refund.succeeded'  => RefundWebhookPayloadParser::class,
			'refund.failed'     => RefundWebhookPayloadParser::class,
		);
	}

	public function getEvents(): array {
		return array_keys( $this->events_processors );
	}

	public function getEventsProcessors(): array {
		return $this->events_processors;
	}

	public function getEventsParsers(): array {
		return $this->events_parsers;
	}

	public function getEventProcessor( string $event_type ): WebhookProcessor {
		// TODO: CP: Confirm whether we should throw an error if we do not recognise the event type?
		if ( ! array_key_exists( $event_type, $this->events_processors ) ) {
			yoco( Logger::class )->logError( sprintf( 'Unknown event type to process: %s.', $event_type ) );
			// translators: Event type.
			throw new Error( sprintf( esc_html__( 'Unknown event type to process: %s.', 'yoco-payment-gateway' ), esc_html( $event_type ) ) );
		}

		return new $this->events_processors[ $event_type ]();
	}

	public function getEventParser( string $event_type ): WebhookPayloadParser {
		// TODO: CP: Confirm whether we should throw an error if we do not recognise the event type?
		if ( ! array_key_exists( $event_type, $this->events_parsers ) ) {
			// translators: Event type.
			yoco( Logger::class )->logError( sprintf( 'Unknown event type to parse: %s.', esc_html( $event_type ) ) );
			// translators: Event Type.
			throw new Error( sprintf( esc_html__( 'Unknown event type to parse: %s.', 'yoco-payment-gateway' ), esc_html( $event_type ) ) );
		}

		return new $this->events_parsers[ $event_type ]();
	}
}
