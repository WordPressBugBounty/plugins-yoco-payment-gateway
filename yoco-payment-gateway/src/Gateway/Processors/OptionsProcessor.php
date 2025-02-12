<?php

namespace Yoco\Gateway\Processors;

use Exception;
use Yoco\Gateway\Gateway;
use Yoco\Helpers\Admin\Notices;
use Yoco\Helpers\Logger;
use Yoco\Installation\Installation;
use Yoco\Installation\Request;

use function Yoco\yoco;

class OptionsProcessor {

	private ?Gateway $gateway = null;

	private ?Installation $installation = null;

	public function __construct( Gateway $gateway ) {
		$this->gateway      = $gateway;
		$this->installation = yoco( Installation::class );
	}

	public function process() {
		try {

			if ( ! $this->gateway->mode->isEnabled() ) {
				return;
			}

			$this->validateKeys();
			$installationRequest = new Request();

			$response = $installationRequest->send();

			// Retry the request for 408, 409, 425, 429, 502, 503, 504 response codes.
			if ( in_array( $response['code'], array( 408, 409, 425, 429, 502, 503, 504 ) ) ) {
				$response = $installationRequest->send();
			}

			// If we get 500 response code, reset Idempotence Key and retry the request.
			if ( in_array( $response['code'], array( 500 ) ) ) {
				add_filter( 'yoco_payment_gateway/installation/request/headers', array( $this, 'resetIdempotenceKey' ) );

				$response = $installationRequest->send();
			}

			if ( ! in_array( $response['code'], array( 200, 201, 202 ) ) ) {
				$error_message = isset( $response['body']['errorMessage'] ) ? $response['body']['errorMessage'] : '';
				$error_code    = isset( $response['body']['errorCode'] ) ? $response['body']['errorCode'] : '';
				$error_string  = "\n" . $response['code'] . ': ' . $response['message'] . ( $error_message ? "\n" . $error_message : '' ) . ( $error_code ? "\n" . $error_code : '' );
				yoco( Logger::class )->logError(
					sprintf(
						__( 'Failed to request installation. %s', 'yoco_wc_payment_gateway' ),
						$error_string
					)
				);

				throw new Exception( sprintf( __( 'Failed to request installation. %s', 'yoco_wc_payment_gateway' ), $error_string ) );
			}

			$this->saveInstallationData( $response['body'] );
		} catch ( \Throwable $th ) {
			$this->displayFailureNotice( $th );
		}

		return true;
	}

	private function saveInstallationData( array $response ) {
		if ( ! isset( $response['id'] ) || empty( $response['id'] ) ) {
			yoco( Logger::class )->logError( 'Response missing installation ID.' );
			throw new Exception( __( 'Response missing installation ID.', 'yoco_wc_payment_gateway' ) );
		}

		$this->installation->saveId( $response['id'] );

		if (
			! isset( $response['subscription'] )
			|| ! isset( $response['subscription']->secret )
			|| empty( $response['subscription']->secret )
		) {
			yoco( Logger::class )->logError( 'Response missing subscription secret.' );
			throw new Exception( __( 'Response missing subscription secret.', 'yoco_wc_payment_gateway' ) );
		}

		$this->installation->saveWebhookSecret( $response['subscription']->secret );

		$this->displaySuccessNotice();
	}

	private function displaySuccessNotice(): void {
		yoco( Notices::class )->renderNotice( 'info', __( 'Plugin installed successfully.', 'yoco_wc_payment_gateway' ) );
	}

	private function displayFailureNotice( \Throwable $th ): void {
		yoco( Notices::class )->renderNotice( 'warning', sprintf( __( 'Failed to install plugin. %s', 'yoco_wc_payment_gateway' ), $th->getMessage() ) );
	}

	private function validateKeys(): void {
		if ( 'test' === $this->gateway->mode->getMode() && empty( preg_match( '/^sk_test/', $this->gateway->credentials->getTestSecretKey() ) ) ) {
			yoco( Notices::class )->renderNotice( 'warning', __( 'Please check the formatting of the secret key.', 'yoco_wc_payment_gateway' ) );
			yoco( Logger::class )->logError( 'Test secret key seem to be invalid.' );
			throw new Exception( __( 'Test secret key seem to be invalid.', 'yoco_wc_payment_gateway' ) );
		}

		if ( 'live' === $this->gateway->mode->getMode() && empty( preg_match( '/^sk_live/', $this->gateway->credentials->getLiveSecretKey() ) ) ) {
			yoco( Notices::class )->renderNotice( 'warning', __( 'Please check the formatting of the secret key.', 'yoco_wc_payment_gateway' ) );
			yoco( Logger::class )->logError( 'Live secret key seem to be invalid.' );
			throw new Exception( __( 'Live secret key seem to be invalid.', 'yoco_wc_payment_gateway' ) );
		}
	}

	public function resetIdempotenceKey( $headers ) {
		if ( ! isset( $headers['Idempotency-Key'] ) || ! is_scalar( $headers['Idempotency-Key'] ) ) {
			return $headers;
		}

		$headers['Idempotency-Key'] = hash( 'SHA256', $headers['Idempotency-Key'] . time() );

		return $headers;
	}
}
