<?php

namespace Yoco\Gateway;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Provider {

	public function __construct() {
		add_filter( 'woocommerce_payment_gateways', array( $this, 'addPaymentMethod' ) );
	}

	public function addPaymentMethod( array $methods ): array {
		$methods[] = Gateway::class;

		return $methods;
	}
}
