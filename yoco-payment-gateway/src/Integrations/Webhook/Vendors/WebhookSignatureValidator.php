<?php

namespace Yoco\Integrations\Webhook\Vendors;

use Exception;
use Yoco\Helpers\Logger;
use function Yoco\yoco;

/**
 * @see https://github.com/svix/svix-webhooks/blob/main/php/src/Webhook.php
 */
class WebhookSignatureValidator {

	const SECRET_PREFIX = 'whsec_';
	const TOLERANCE     = 5 * 60;
	private $secret;

	public function __construct( $secret ) {
		if ( substr( $secret, 0, strlen( self::SECRET_PREFIX ) ) === self::SECRET_PREFIX ) {
			$secret = substr( $secret, strlen( self::SECRET_PREFIX ) );
		}

		$this->secret = base64_decode( $secret ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	}

	public static function fromRaw( $secret ) {
		$obj         = new self( $secret );
		$obj->secret = $secret;
		return $obj;
	}

	public function verify( $payload, $headers ) {
		if (
			! isset( $headers['webhook_id'] )
			|| ! isset( $headers['webhook_timestamp'] )
			|| ! isset( $headers['webhook_signature'] )
		) {
			yoco( Logger::class )->logError( 'Webhook Signature Validator is missing required headers' );
			throw new Exception( 'Webhook Signature Validator is missing required headers' );
		}

		$msgId        = $headers['webhook_id'];
		$msgTimestamp = $headers['webhook_timestamp'];
		$msgSignature = $headers['webhook_signature'];

		$timestamp = self::verifyTimestamp( $msgTimestamp );

		$signature         = $this->sign( $msgId, $timestamp, $payload );
		$expectedSignature = explode( ',', $signature, 2 )[1];

		$passedSignatures = explode( ' ', $msgSignature );
		foreach ( $passedSignatures as $versionedSignature ) {
			$sigParts        = explode( ',', $versionedSignature, 2 );
			$version         = $sigParts[0];
			$passedSignature = $sigParts[1];

			if ( 0 !== strcmp( $version, 'v1' ) ) {
				continue;
			}

			if ( hash_equals( $expectedSignature, $passedSignature ) ) {
				return json_decode( $payload, true );
			}
		}
		yoco( Logger::class )->logError( 'Webhook no matching signature found' );
		throw new Exception( 'Webhook no matching signature found' );
	}

	public function sign( string $msgId, int $timestamp, string $payload ): string {
		if ( ! is_int( $timestamp ) ) {
			yoco( Logger::class )->logError( 'Invalid timestamp format' );
			throw new Exception( 'Invalid timestamp format' );
		}
		$toSign    = "{$msgId}.{$timestamp}.{$payload}";
		$hex_hash  = hash_hmac( 'sha256', $toSign, $this->secret );
		$signature = base64_encode( pack( 'H*', $hex_hash ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return "v1,{$signature}";
	}

	private function verifyTimestamp( $timestampHeader ): int {
		$now       = time();
		$timestamp = intval( $timestampHeader, 10 );

		if ( $timestamp < ( $now - self::TOLERANCE ) ) {
			yoco( Logger::class )->logError( 'Webhook timestamp is too old' );
			throw new Exception( 'Webhook timestamp is too old' );
		}

		if ( $timestamp > ( $now + self::TOLERANCE ) ) {
			yoco( Logger::class )->logError( 'Webhook timestamp is too new' );
			throw new Exception( 'Webhook timestamp is too new' );
		}

		return $timestamp;
	}
}
