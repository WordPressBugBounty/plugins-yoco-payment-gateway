<?php

namespace Yoco\Installations;

use Yoco\Helpers\Logger;

use function Yoco\yoco;

class InstallationsManager {

	public const INSTALLATION_ID_OPTION_LIVE_KEY = 'yoco_payment_gateway_installation_live_id';

	public const INSTALLATION_ID_OPTION_TEST_KEY = 'yoco_payment_gateway_installation_test_id';

	public const WEBHOOK_SECRET_OPTION_LIVE_KEY = 'yoco_payment_gateway_live_webhook_secret';

	public const WEBHOOK_SECRET_OPTION_TEST_KEY = 'yoco_payment_gateway_test_webhook_secret';

	private ?string $installationId = null;

	private ?string $webhookSecret = null;

	public function __construct() {
		add_action( 'yoco_payment_gateway/installation/success', array( $this, 'setInstallationId' ), 10, 2 );
		add_action( 'yoco_payment_gateway/installation/success', array( $this, 'setWebhookSecret' ), 10, 2 );
	}

	public function setInstallationId( array $response, string $mode ): void {
		$key = 'live' === $mode ? self::INSTALLATION_ID_OPTION_LIVE_KEY : self::INSTALLATION_ID_OPTION_TEST_KEY;

		if ( ! isset( $response['id'] ) ) {
			yoco( Logger::class )->logError( 'Response is missing installation ID.' );
			return;
		}

		$current_id = get_option( $key );

		if ( $current_id === $response['id'] ) {
			return;
		}

		$updated = update_option( $key, $response['id'] );

		if ( false === $updated ) {
			yoco( Logger::class )->logError( 'Failed to save Yoco installation ID.' );
			return;
		}

		yoco( Logger::class )->logInfo( 'Successfully saved new installation ID.' );
	}

	public function getInstallationId( string $mode ): string {
		$key = 'live' === $mode ? self::INSTALLATION_ID_OPTION_LIVE_KEY : self::INSTALLATION_ID_OPTION_TEST_KEY;

		if ( null === $this->installationId ) {
			$this->installationId = get_option( $key, '' );
		}

		return $this->installationId;
	}

	public function hasInstallationId( string $mode ): bool {
		return ! empty( $this->getInstallationId( $mode ) );
	}

	public function setWebhookSecret( array $response, string $mode ): void {
		$key = 'live' === $mode ? self::WEBHOOK_SECRET_OPTION_LIVE_KEY : self::WEBHOOK_SECRET_OPTION_TEST_KEY;

		if ( ! isset( $response['subscription'] ) ) {
			yoco( Logger::class )->logError( 'Response is missing subscription secret.' );
			return;
		}

		$current_secret = get_option( $key );

		if ( $current_secret === $response['subscription'] ) {
			return;
		}

		$updated = update_option( $key, $response['subscription'] );

		if ( false === $updated ) {
			yoco( Logger::class )->logError( 'Failed to save subscription secret.' );
			return;
		}

		yoco( Logger::class )->logInfo( 'Successfully saved new subscription secret.' );
	}

	public function getWebhookSecret( string $mode ): string {
		$key = 'live' === $mode ? self::WEBHOOK_SECRET_OPTION_LIVE_KEY : self::WEBHOOK_SECRET_OPTION_TEST_KEY;

		if ( null === $this->webhookSecret ) {
			$this->webhookSecret = get_option( $key, '' );
		}

		return $this->webhookSecret;
	}

	public function hasWebhookSecret( string $mode ): bool {
		return ! empty( $this->getWebhookSecret( $mode ) );
	}
}
