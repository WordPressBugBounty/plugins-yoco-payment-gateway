<?php

namespace Yoco\Gateway;

class Debug {

	private ?Gateway $gateway = null;

	public function __construct( Gateway $gateway ) {
		$this->gateway = $gateway;
	}

	public function isEnabled(): bool {
		return wc_string_to_bool( $this->gateway->get_option( 'debug', 'yes' ) );
	}
}
