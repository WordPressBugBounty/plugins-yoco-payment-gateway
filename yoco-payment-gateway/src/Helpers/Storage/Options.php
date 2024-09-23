<?php

namespace Yoco\Helpers\Storage;

class Options {

	private array $options = array();

	public function retrieve(): void {
		$this->options = $this->getOptions();
	}

	public function getOptions(): ?array {
		$option = get_option( 'woocommerce_class_yoco_wc_payment_gateway_settings', null );

		return is_array( $option ) ? $option : array();
	}

	public function getOption( string $key ): string {
		return array_key_exists( $key, $this->options ) ? $this->options[ $key ] : '';
	}

	public function hasOption( string $key ): bool {
		return ! empty( $this->getOption( $key ) );
	}
}
