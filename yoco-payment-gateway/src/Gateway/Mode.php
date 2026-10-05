<?php

namespace Yoco\Gateway;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mode {

	private ?Gateway $gateway = null;

	public function __construct( Gateway $gateway ) {
		$this->gateway = $gateway;
	}

	public function isEnabled(): bool {
		return wc_string_to_bool( $this->gateway->get_option( 'enabled', 'no' ) );
	}

	public function getMode(): string {
		return $this->gateway->get_option( 'mode' );
	}

	public function isLiveMode(): bool {
		return $this->isEnabled() && 'live' === $this->getMode();
	}

	public function isTestMode(): bool {
		return $this->isEnabled() && 'test' === $this->getMode();
	}
}
