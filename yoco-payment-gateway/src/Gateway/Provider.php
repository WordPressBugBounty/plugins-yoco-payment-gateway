<?php

namespace Yoco\Gateway;

class Provider {

	private $gateway;

	public function __construct() {
		add_filter( 'woocommerce_payment_gateways', array( $this, 'addPaymentMethod' ) );
	}

	public function getGateway(): Gateway {
		if ( null === $this->gateway ) {
			$instance = $this->getInstance();

			$this->gateway = $instance ?? new Gateway();
		}

		return $this->gateway;
	}

	public function addPaymentMethod( array $methods ): array {
		$methods[] = Gateway::class;

		return $methods;
	}

	private function getInstance(): ?Gateway {
		$gateways = WC()->payment_gateways()->get_available_payment_gateways();

		if ( ! array_key_exists( 'class_yoco_wc_payment_gateway', $gateways ) ) {
			return null;
		}

		return $gateways['class_yoco_wc_payment_gateway'];
	}
}
