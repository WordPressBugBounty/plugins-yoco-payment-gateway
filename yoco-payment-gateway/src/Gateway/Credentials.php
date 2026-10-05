<?php

namespace Yoco\Gateway;

use Yoco\Core\Constants;

use function Yoco\yoco;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Credentials {

	public const MODES = array( 'live', 'test' );

	private ?Gateway $gateway = null;

	private ?string $livePublic = null;

	private ?string $liveSecret = null;

	private ?string $testPublic = null;

	private ?string $testSecret = null;

	public function __construct( Gateway $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Tell whether a secret key has the valid format for the given mode.
	 *
	 * @param  string $mode Gateway mode live|test.
	 * @param  string $key Secret key.
	 *
	 * @return bool
	 *
	 * @since 3.9.5
	 */
	public static function isValidSecretKey( string $mode, string $key ): bool {
		if ( ! in_array( $mode, self::MODES, true ) ) {
			return false;
		}

		// Same prefix rule as admin.js, plus a non-empty secret.
		return 1 === preg_match( '/^sk_' . $mode . '_\S+$/', $key );
	}

	public function getLivePublicKey(): string {
		if ( null === $this->livePublic ) {
			$this->livePublic = $this->gateway->mode->isLiveMode() ? $this->gateway->get_option( 'live_public_key', '' ) : '';
		}

		return $this->livePublic;
	}

	public function getLiveSecretKey(): string {
		if ( null === $this->liveSecret ) {
			$this->liveSecret = $this->gateway->mode->isLiveMode() ? $this->gateway->get_option( 'live_secret_key', '' ) : '';
		}

		return $this->liveSecret;
	}

	public function getTestPublicKey(): string {
		if ( null === $this->testPublic ) {
			$this->testPublic = $this->gateway->mode->isTestMode() ? $this->gateway->get_option( 'test_public_key', '' ) : '';
		}

		return $this->testPublic;
	}

	public function getTestSecretKey(): string {
		if ( null === $this->testSecret ) {
			$this->testSecret = $this->gateway->mode->isTestMode() ? $this->gateway->get_option( 'test_secret_key', '' ) : '';
		}

		return $this->testSecret;
	}

	public function hasLiveKeys(): bool {
		return ! empty( $this->livePublic && ! empty( $this->liveSecret ) );
	}

	public function hasTestKeys(): bool {
		return ! empty( $this->testPublic && ! empty( $this->testSecret ) );
	}

	public function getSecretKey(): string {
		if ( $this->gateway->mode->isTestMode() ) {
			return $this->getTestSecretKey();
		}

		if ( $this->gateway->mode->isLiveMode() ) {
			return $this->getLiveSecretKey();
		}

		return '';
	}

	public function getCheckoutApiUrl(): string {
		/**
		 * @var Constants $constants
		 */
		$constants = yoco( Constants::class );

		if ( $constants->hasCheckoutApiUrl() ) {
			return $constants->getCheckoutApiUrl();
		}

		return '';
	}

	public function getInstallationApiUrl(): string {
		/**
		 * @var Constants $constants
		 */
		$constants = yoco( Constants::class );

		if ( $constants->hasInstallationApiUrl() ) {
			return $constants->getInstallationApiUrl();
		}

		return '';
	}
}
