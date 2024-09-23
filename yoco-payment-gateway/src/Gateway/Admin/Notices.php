<?php

namespace Yoco\Gateway\Admin;

use Yoco\Gateway\Gateway;
use Yoco\Helpers\Admin\Notices as AdminNotices;
use Yoco\Helpers\Money\Currencies;
use Yoco\Helpers\Security\SSL;

use function Yoco\yoco;

class Notices {

	public function __construct() {
		add_action( 'yoco_payment_gateway/admin/display_notices', array( $this, 'showTestModeNotice' ) );
		add_action( 'yoco_payment_gateway/admin/display_notices', array( $this, 'showUnsupportedCurrencyNotice' ) );
		add_action( 'yoco_payment_gateway/admin/display_notices', array( $this, 'showInsecureConnectionNotice' ) );
	}

	public function showTestModeNotice( Gateway $gateway ): void {
		if ( $gateway->mode->isTestMode() ) {
			yoco( AdminNotices::class )->renderNotice( 'info', __( 'Test mode enabled.', 'yoco_wc_payment_gateway' ) );
		}
	}

	public function showUnsupportedCurrencyNotice(): void {
		/**
		 * @var Currencies
		 */
		$currencies = yoco( Currencies::class );

		if ( ! $currencies->isCurrentCurrencySupported() ) {
			yoco( AdminNotices::class )->renderNotice( 'warning', sprintf( __( 'Currency is not supported (%s).', 'yoco_wc_payment_gateway' ), $currencies->getCurrentCurrency() ) );
		}
	}

	public function showInsecureConnectionNotice(): void {
		/**
		 * @var SSL
		 */
		$ssl = yoco( SSL::class );

		if ( ! $ssl->isSecure() ) {
			yoco( AdminNotices::class )->renderNotice( 'warning', __( 'Payment method not available for unsafe websites (SSL).', 'yoco_wc_payment_gateway' ) );
		}
	}
}
