<?php

namespace Yoco\Core;

class Constants {

	public function getInstallationApiUrl(): string {
		if ( ! defined( 'YOCO_INSTALL_API_URL' ) ) {
			return '';
		}

		return YOCO_INSTALL_API_URL;
	}

	public function hasInstallationApiUrl(): bool {
		return ! empty( $this->getInstallationApiUrl() );
	}

	public function getCheckoutApiUrl(): ?string {
		if ( ! defined( 'YOCO_ONLINE_CHECKOUT_URL' ) ) {
			return '';
		}

		return YOCO_ONLINE_CHECKOUT_URL;
	}

	public function hasCheckoutApiUrl(): bool {
		return ! empty( $this->getCheckoutApiUrl() );
	}

	public function getPaymentApiUrl(): ?string {
		if ( ! defined( 'YOCO_ONLINE_PAYMENT_URL' ) ) {
			return '';
		}

		return YOCO_ONLINE_PAYMENT_URL;
	}

	public function hasPaymentApiUrl(): bool {
		return ! empty( $this->getPaymentApiUrl() );
	}
}
