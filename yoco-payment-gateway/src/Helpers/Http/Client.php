<?php

namespace Yoco\Helpers\Http;

use Exception;
use Yoco\Helpers\Logger;
use function Yoco\yoco;

class Client {

	public function post( string $url, array $args ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			yoco( Logger::class )->logError( 'Invalid URL for POST request.' );
			throw new Exception( __( 'Invalid URL for POST request.', 'yoco_wc_payment_gateway' ) );
		}

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			yoco( Logger::class )->logError(
				'Invalid response: ' . $response->get_error_message() . ' code: ' . $response->get_error_code()
			);
			throw new Exception( $response->get_error_message(), 0 );
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
			throw new Exception( $response->get_error_message(), 0 );
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

