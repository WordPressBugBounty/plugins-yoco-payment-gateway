<?php

namespace Yoco\Gateway;

class Mode {

	private ?Gateway $gateway = null;

	public function __construct( Gateway $gateway ) {
		$this->gateway = $gateway;
	}

	public function isEnabled(): bool {
		$enabled = isset( $_POST['woocommerce_class_yoco_wc_payment_gateway_enabled'] ) ? $_POST['woocommerce_class_yoco_wc_payment_gateway_enabled'] : $this->gateway->get_option( 'enabled', 'no' );
		return wc_string_to_bool( $enabled );
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
