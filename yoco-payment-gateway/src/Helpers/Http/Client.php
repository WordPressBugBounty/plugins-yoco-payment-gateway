<?php

namespace Yoco\Helpers\Http;

use Exception;
use Yoco\Helpers\Logger;
use function Yoco\yoco;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Client {

	// Worth one retry.
	public const TRANSIENT_STATUS_CODES = array( 408, 409, 425, 429, 502, 503, 504 );

	// Recover once the merchant fixes the secret key.
	public const UNAUTHORIZED_STATUS_CODES = array( 401, 403 );

	public function post( string $url, array $args ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			yoco( Logger::class )->logError( 'Invalid URL for POST request.' );
			throw new Exception( esc_html__( 'Invalid URL for POST request.', 'yoco-payment-gateway' ) );
		}

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			yoco( Logger::class )->logError(
				'Invalid response: ' . $response->get_error_message() . ' code: ' . $response->get_error_code()
			);
			throw new Exception( esc_html( $response->get_error_message() ), 0 );
		}

		$code    = wp_remote_retrieve_response_code( $response );
		$message = wp_remote_retrieve_response_message( $response );
		$body    = wp_remote_retrieve_body( $response );

		$data = array(
			'code'    => $code,
			'message' => $message,
			'body'    => (array) json_decode( $body ),
		);

		if ( apply_filters( 'yoco_payment_gateway_debug', false ) ) {
			$data['args'] = $args;
			if ( isset( $data['args']['headers']['Authorization'] ) ) {
				$data['args']['headers']['Authorization'] = 'xxxxx';
			}
		}

		return $data;
	}

	public function get( string $url, array $args ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			yoco( Logger::class )->logError( 'Invalid URL for GET request.' );
			throw new Exception( 'Invalid URL for GET request.' );
		}

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			yoco( Logger::class )->logError(
				'Invalid response: ' . $response->get_error_message() . ' code: ' . $response->get_error_code()
			);
			throw new Exception( esc_html( $response->get_error_message() ), 0 );
		}

		$code    = wp_remote_retrieve_response_code( $response );
		$message = wp_remote_retrieve_response_message( $response );
		$body    = wp_remote_retrieve_body( $response );

		return array(
			'code'    => $code,
			'message' => $message,
			'body'    => (array) json_decode( $body ),
		);
	}
}
