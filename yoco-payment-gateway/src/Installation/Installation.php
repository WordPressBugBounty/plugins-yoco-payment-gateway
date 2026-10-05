<?php

namespace Yoco\Installation;

use Exception;
use Yoco\Helpers\Logger;
use Yoco\Core\Constants;
use Yoco\Gateway\Credentials;

use function Yoco\yoco;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Installation {

	private ?array $settings = null;

	public function __construct() {
		// The settings are read early in every request, so a save would leave the install request with the old values.
		add_action( 'add_option_woocommerce_class_yoco_wc_payment_gateway_settings', array( $this, 'resetSettings' ) );
		add_action( 'update_option_woocommerce_class_yoco_wc_payment_gateway_settings', array( $this, 'resetSettings' ) );
	}

	/**
	 * Drop the cached settings so the next read gets the stored values.
	 *
	 * Hooked to `add_option_` and `update_option_woocommerce_class_yoco_wc_payment_gateway_settings`.
	 *
	 * @return void
	 *
	 * @since 3.9.5
	 */
	public function resetSettings(): void {
		$this->settings = null;
	}

	public function getSettings(): array {
		if ( null === $this->settings ) {
			// Defaults mirror the form fields in Gateway\Settings.
			$this->settings = wp_parse_args(
				get_option( 'woocommerce_class_yoco_wc_payment_gateway_settings' ),
				array(
					'enabled'         => 'no',
					'mode'            => 'test',
					'live_secret_key' => '',
					'test_secret_key' => '',
					'debug'           => 'yes',
				)
			);
		}

		return $this->settings;
	}

	public function isEnabled() {
		return isset( $this->getSettings()['enabled'] ) ? wc_string_to_bool( $this->getSettings()['enabled'] ) : '';
	}

	public function isDebugEnabled() {
		return isset( $this->getSettings()['debug'] ) ? wc_string_to_bool( $this->getSettings()['debug'] ) : '';
	}

	public function getMode() {
		return isset( $this->getSettings()['mode'] ) ? $this->getSettings()['mode'] : '';
	}

	public function getSecretKey( string $mode = '' ) {
		$mode     = $this->resolveMode( $mode );
		$settings = $this->getSettings();

		return isset( $settings[ $mode . '_secret_key' ] ) && is_string( $settings[ $mode . '_secret_key' ] ) ? $settings[ $mode . '_secret_key' ] : '';
	}

	/**
	 * Resolve the gateway mode to use for a request.
	 *
	 * @param  string $mode Gateway mode live|test, anything else falls back to the stored mode.
	 *
	 * @return string The mode, empty when no mode is stored.
	 */
	private function resolveMode( string $mode ): string {
		return in_array( $mode, Credentials::MODES, true ) ? $mode : $this->getMode();
	}

	public function getApiUrl(): string {
		/**
		 * @var Constants $constants
		 */
		$constants = yoco( Constants::class );

		if ( $constants->hasInstallationApiUrl() ) {
			return $constants->getInstallationApiUrl();
		}

		return '';
	}

	public function getCheckoutApiUrl(): string {
		/**
		 * @var Constants $constants
		 */
		$constants = yoco( Constants::class );

		if ( $constants->getCheckoutApiUrl() ) {
			return $constants->getCheckoutApiUrl();
		}

		return '';
	}

	public function getPaymentApiUrl(): string {
		/**
		 * @var Constants $constants
		 */
		$constants = yoco( Constants::class );

		if ( $constants->getPaymentApiUrl() ) {
			return $constants->getPaymentApiUrl();
		}

		return '';
	}

	/**
	 * Build the Authorization header value for the resolved mode.
	 *
	 * @param  string $mode Gateway mode live|test, empty resolves to the stored mode.
	 *
	 * @return string
	 *
	 * @throws MissingSecretKeyException When no secret key is stored for the mode.
	 */
	public function getApiBearer( string $mode = '' ): string {
		$mode   = $this->resolveMode( $mode );
		$secret = $this->getSecretKey( $mode );

		if ( '' === $secret ) {
			yoco( Logger::class )->logError( '' !== $mode ? sprintf( 'Missing %s secret key.', $mode ) : 'Missing secret key, no mode selected.' );

			$message = '' !== $mode
				// translators: Gateway mode live|test.
				? sprintf( __( 'Missing %s secret key. Please check the Yoco Payments settings.', 'yoco-payment-gateway' ), $mode )
				: __( 'Missing secret key. Please select a mode and check the Yoco Payments settings.', 'yoco-payment-gateway' );

			throw new MissingSecretKeyException( esc_html( $message ) );
		}

		return 'Bearer ' . $secret;
	}

	public function getIdMetaKey(): string {

		return 'yoco_payment_gateway_installation_' . $this->getMode() . '_id';
	}

	public function getWebhookSecretMetaKey(): string {
		return 'yoco_payment_gateway_' . $this->getMode() . '_webhook_secret';
	}

	public function saveId( string $id ): void {
		$key        = $this->getIdMetaKey();
		$current_id = get_option( $key );

		if ( $current_id === $id ) {
			return;
		}

		$updated = update_option( $key, $id );

		if ( false === $updated ) {
			yoco( Logger::class )->logError( 'Failed to save Webhook Secret option.', 'yoco-payment-gateway' );

			throw new Exception( esc_html__( 'Failed to save Webhook Secret option.', 'yoco-payment-gateway' ) );
		}
	}

	public function getId() {
		return get_option( $this->getIdMetaKey() );
	}

	public function saveWebhookSecret( string $secret ): void {
		$key            = $this->getWebhookSecretMetaKey();
		$current_secret = get_option( $key );

		if ( $current_secret === $secret ) {
			return;
		}

		$updated = update_option( $key, $secret );

		if ( false === $updated ) {
			yoco( Logger::class )->logError( 'Failed to save installation ID option.' );

			throw new Exception( esc_html__( 'Failed to save installation ID option.', 'yoco-payment-gateway' ) );
		}
	}

	public function getWebhookSecret() {
		return get_option( $this->getWebhookSecretMetaKey() );
	}
}
