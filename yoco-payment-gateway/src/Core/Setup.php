<?php

namespace Yoco\Core;

use Yoco\Helpers\Admin\Notices;
use Yoco\Helpers\Logger;
use Yoco\Helpers\Security\SSL;
use Yoco\Telemetry\Telemetry;
use Yoco\Telemetry\Models\TelemetryObject;
use Yoco\Installation\Installation;

use function Yoco\yoco;

/**
 * Setup class.
 */
class Setup {

	public function __construct() {
		add_action( 'woocommerce_init', array( $this, 'maybe_deactivate_on_incompatible_env' ) );
	}

	public function maybe_deactivate_on_incompatible_env(): void {
		if ( ! $this->isEnabled() ) {
			return;
		}

		$this->deactivateOnIncompatibileEnv();
	}

	public function isEnabled() {
		return yoco( Installation::class )->isEnabled();
	}

	public function deactivateOnIncompatibileEnv(): bool {

		if ( ! yoco( SSL::class )->isSecure() ) {
			$this->deactivateAsIncompatibileEnv(
				'Error: plugin temporary suspended due to SSL certificate issue. Possible reasons for this suspension: SSL Certificate Expiry, Insecure SSL Configuration or SSL Handshake Failure.',
				$this->getEnvironmentData( array( 'HTTPS', 'REQUEST_SCHEME', 'SERVER_PORT', 'HTTP_HOST', 'REQUEST_URI' ) ),
				array(
					'source' => 'yoco-gateway-v' . YOCO_PLUGIN_VERSION . '-ssl-error-' . yoco( Installation::class )->getMode() . '_mode',
				)
			);
			return false;
		}

		if ( ! yoco( Installation::class )->getId() ) {

			if ( ! $this->isEnabled() ) {
				return true;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['woocommerce_class_yoco_wc_payment_gateway_enabled'] ) ) {
				return true;
			}

			if ( YOCO_PLUGIN_VERSION !== get_option( 'yoco_wc_payment_gateway_version' ) ) {
				return true;
			}

			$this->deactivateAsIncompatibileEnv(
				sprintf(
					// translators: link open and link closing tag.
					__( 'Error: plugin suspended due to missing Installation ID. Please visit %1$sYoco Payments settings%2$s and "Save changes". Make sure Secret Keys are correct.', 'yoco_wc_payment_gateway' ),
					'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=class_yoco_wc_payment_gateway' ) . '">',
					'</a>',
				)
			);
			return false;
		}

		if ( ! yoco( Installation::class )->getWebhookSecret() ) {

			if ( ! $this->isEnabled() ) {
				return true;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['woocommerce_class_yoco_wc_payment_gateway_enabled'] ) ) {
				return true;
			}

			if ( YOCO_PLUGIN_VERSION !== get_option( 'yoco_wc_payment_gateway_version' ) ) {
				return true;
			}

			$this->deactivateAsIncompatibileEnv(
				sprintf(
					// translators: link open and link closing tag.
					__( 'Error: plugin suspended due to missing Subscription ID. Please visit %1$sYoco Payments settings%2$s and "Save changes". Make sure Secret Keys are correct.', 'yoco_wc_payment_gateway' ),
					'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=class_yoco_wc_payment_gateway' ) . '">',
					'</a>',
				)
			);
			return false;
		}

		/**
		 * @var TelemetryObject $telemetry
		 */
		$telemetry = yoco( Telemetry::class )->getObject();

		if ( version_compare( $telemetry->getPhpVersion(), '7.4.0', '<' ) ) {
			$this->deactivateAsIncompatibileEnv( __( 'Error: plugin suspended due to incompatible PHP version. Required PHP version is 7.4.0 or higher.', 'yoco_wc_payment_gateway' ) );
			return false;
		}

		if ( version_compare( $telemetry->getWpVersion(), '5.0.0', '<' ) ) {
			$this->deactivateAsIncompatibileEnv( __( 'Error: plugin suspended due to incompatible WordPress version. Required WordPress version 5.0 or higher.', 'yoco_wc_payment_gateway' ) );
			return false;
		}

		if ( version_compare( $telemetry->getWcVersion(), '4.0.0', '<' ) ) {
			$this->deactivateAsIncompatibileEnv( __( 'Error: plugin suspended due to incompatible WooCommerce version. Required WooCommerce version 4.0 or higher.', 'yoco_wc_payment_gateway' ) );
			return false;
		}

		do_action( 'yoco_payment_gateway/plugin/compatible' );

		return true;
	}

	private function deactivateAsIncompatibileEnv( string $message = '', $env_data = '', array $context = array() ): void {
		static $errors;

		$index = md5( $message );

		if ( isset( $errors[ $index ] ) ) {
			return;
		}

		if ( $message ) {
			$errors[ $index ] = true;
		}
		$env_data = $env_data ? "\n" . $env_data : '';
		yoco( Logger::class )->logError( wp_strip_all_tags( $message ) . $env_data, $context );
		yoco( Notices::class )->renderNotice( 'error', $message );
	}

	private function getEnvironmentData( $elements = array() ) {
		$message = '';
		foreach ( $elements as $element ) {
			$value = isset( $_SERVER[ $element ] ) ? $_SERVER[ $element ] : '-';
			$message .= $element . ': ' . $value . "\n";
		}

		return $message;
	}
}
