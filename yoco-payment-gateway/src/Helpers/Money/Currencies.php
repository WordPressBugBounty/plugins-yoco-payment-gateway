<?php

namespace Yoco\Helpers\Money;

class Currencies {

	public function getSupportedCurrencies(): array {
		return apply_filters(
			'yoco_gateway_plugin/money/currencies',
			array(
				'ZAR',
			)
		);
	}

	public function getCurrentCurrency(): string {
		return get_woocommerce_currency();
	}

	public function isCurrentCurrencySupported(): bool {
		return in_array( $this->getCurrentCurrency(), $this->getSupportedCurrencies() );
	}
}
