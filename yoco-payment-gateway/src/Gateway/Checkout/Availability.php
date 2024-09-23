<?php

namespace Yoco\Gateway\Checkout;

use Yoco\Gateway\Gateway;
use Yoco\Helpers\Security\SSL;
use Yoco\Installations\InstallationsManager;

use function Yoco\yoco;

class Availability {

	public function __construct() {
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disableIfInsecureConnection' ) );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disableIfIGatewayNotEnabled' ) );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disableIfMissingCheckoutUrl' ) );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disableIfMissingCredentials' ) );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disableIfMissingInstallation' ) );
	}

	public function disableIfInsecureConnection( array $availableGateways ): array {
		if ( is_admin() || ! $this->isYocoGatewayAvailable( $availableGateways ) ) {
			return $availableGateways;
		}

		/**
		 * @var SSL $ssl
		 */
		$ssl = yoco( SSL::class );

		if ( ! $ssl->isSecure() ) {
			unset( $availableGateways['class_yoco_wc_payment_gateway'] );
		}

		return $availableGateways;
	}

	public function disableIfIGatewayNotEnabled( array $availableGateways ): array {
		if ( is_admin() || ! $this->isYocoGatewayAvailable( $availableGateways ) ) {
			return $availableGateways;
		}

		/**
		 * @var Gateway
		 */
		$gateway = $availableGateways['class_yoco_wc_payment_gateway'];

		if ( 'yes' !== $gateway->get_option( 'enabled' ) ) {
			unset( $availableGateways['class_yoco_wc_payment_gateway'] );
		}

		return $availableGateways;
	}

	public function disableIfMissingCheckoutUrl( array $availableGateways ): array {
		if ( is_admin() || ! $this->isYocoGatewayAvailable( $availableGateways ) ) {
			return $availableGateways;
		}

		/**
		 * @var Gateway
		 */
		$gateway = $availableGateways['class_yoco_wc_payment_gateway'];

		if ( empty( $gateway->credentials->getCheckoutApiUrl() ) ) {
			unset( $availableGateways['class_yoco_wc_payment_gateway'] );
		}

		return $availableGateways;
	}


	public function disableIfMissingCredentials( array $availableGateways ): array {
		if ( is_admin() || ! $this->isYocoGatewayAvailable( $availableGateways ) ) {
			return $availableGateways;
		}

		/**
		 * @var Gateway
		 */
		$gateway = $availableGateways['class_yoco_wc_payment_gateway'];

		$mode = $gateway->get_option( 'mode' );

		if ( 'test' === $mode && empty( $gateway->get_option( 'test_secret_key' ) ) ) {
			unset( $availableGateways['class_yoco_wc_payment_gateway'] );
		}

		if ( 'live' === $mode && empty( $gateway->get_option( 'live_secret_key' ) ) ) {
			unset( $availableGateways['class_yoco_wc_payment_gateway'] );
		}

		return $availableGateways;
	}

	public function disableIfMissingInstallation( array $availableGateways ): array {
		if ( is_admin() || ! $this->isYocoGatewayAvailable( $availableGateways ) ) {
			return $availableGateways;
		}

		/**
		 * @var Gateway
		 */
		$gateway = $availableGateways['class_yoco_wc_payment_gateway'];

		$mode = $gateway->get_option( 'mode' );

		/**
		 * @var InstallationsManager
		 */
		$installation = yoco( InstallationsManager::class );

		if ( ! $installation->hasInstallationId( $mode ) || ! $installation->hasWebhookSecret( $mode ) ) {
			unset( $availableGateways['class_yoco_wc_payment_gateway'] );
		}

		return $availableGateways;
	}

	private function isYocoGatewayAvailable( array $availableGateways ): bool {
		return array_key_exists( 'class_yoco_wc_payment_gateway', $availableGateways );
	}
}
