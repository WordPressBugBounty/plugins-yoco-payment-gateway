<?php

namespace Yoco\Gateway\Processors;

use Exception;
use Yoco\Gateway\Gateway;
use Yoco\Helpers\Admin\Notices;
use Yoco\Helpers\Http\Client;
use Yoco\Helpers\Logger;
use Yoco\Installation\Installation;
use Yoco\Installation\Request;

use function Yoco\yoco;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OptionsProcessor {

	// Sent by the installation API when Yoco refuses to register the webhook for this site.
	private const WEBHOOK_REGISTRATION_ERROR = 'Unable to create subscription';

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

			$installationRequest = new Request();

			$response = $installationRequest->send();

			// Retry the request once for transient response codes.
			if ( in_array( (int) $response['code'], Client::TRANSIENT_STATUS_CODES, true ) ) {
				$response = $installationRequest->send();
			}

			// If we get 500 response code, reset Idempotence Key and retry the request.
			if ( 500 === (int) $response['code'] ) {
				add_filter( 'yoco_payment_gateway/installation/request/headers', array( $this, 'resetIdempotenceKey' ) );

				$response = $installationRequest->send();
			}

			if ( ! in_array( (int) $response['code'], array( 200, 201, 202 ), true ) ) {
				$error_message = isset( $response['body']['errorMessage'] ) ? $response['body']['errorMessage'] : '';
				$error_code    = isset( $response['body']['errorCode'] ) ? $response['body']['errorCode'] : '';
				$error_string  = "\n" . $response['code'] . ': ' . $response['message'] . ( $error_message ? "\n" . $error_message : '' ) . ( $error_code ? "\n" . $error_code : '' );
				yoco( Logger::class )->logError(
					sprintf(
						// translators: Error message.
						__( 'Failed to request installation. %s', 'yoco-payment-gateway' ),
						$error_string
					)
				);

				if ( is_string( $error_message ) && false !== stripos( $error_message, self::WEBHOOK_REGISTRATION_ERROR ) ) {
					// The API does not say why, in practice the account has reached its webhook limit.
					// translators: Error message.
					throw new Exception( sprintf( __( 'Yoco could not register a webhook for this site, so the site will not be notified about payments and refunds. This usually happens when your Yoco account has reached its maximum number of webhooks. %s', 'yoco-payment-gateway' ), $error_string ) );
				}

				// translators: Error message.
				throw new Exception( sprintf( __( 'Failed to request installation. %s', 'yoco-payment-gateway' ), $error_string ) );
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
			throw new Exception( esc_html__( 'Response missing installation ID.', 'yoco-payment-gateway' ) );
		}

		$this->installation->saveId( $response['id'] );

		if (
			! isset( $response['subscription'] )
			|| ! isset( $response['subscription']->secret )
			|| empty( $response['subscription']->secret )
		) {
			yoco( Logger::class )->logError( 'Response missing subscription secret.' );
			throw new Exception( esc_html__( 'Response missing subscription secret.', 'yoco-payment-gateway' ) );
		}

		$this->installation->saveWebhookSecret( $response['subscription']->secret );

		$this->displaySuccessNotice();
	}

	private function displaySuccessNotice(): void {
		yoco( Notices::class )->renderNotice( 'info', __( 'Plugin installed successfully.', 'yoco-payment-gateway' ) );
	}

	private function displayFailureNotice( \Throwable $th ): void {
		// translators: Error message.
		yoco( Notices::class )->renderNotice( 'warning', sprintf( __( 'Failed to install plugin. %s', 'yoco-payment-gateway' ), $th->getMessage() ) );
	}

	public function resetIdempotenceKey( $headers ) {
		if ( ! isset( $headers['Idempotency-Key'] ) || ! is_scalar( $headers['Idempotency-Key'] ) ) {
			return $headers;
		}

		$headers['Idempotency-Key'] = hash( 'SHA256', $headers['Idempotency-Key'] . time() );

		return $headers;
	}
}
