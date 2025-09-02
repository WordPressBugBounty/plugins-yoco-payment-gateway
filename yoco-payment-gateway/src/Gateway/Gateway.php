<?php

namespace Yoco\Gateway;

use WC_Payment_Gateway;
use Yoco\Gateway\Processors\OptionsProcessor;
use Yoco\Gateway\Processors\PaymentProcessor;
use Yoco\Gateway\Processors\RefundProcessor;
use Yoco\Helpers\Admin\Notices;
use Yoco\Installations\InstallationsManager;

use function Yoco\yoco;

class Gateway extends WC_Payment_Gateway {

	public ?Credentials $credentials = null;

	public ?Mode $mode = null;

	public ?Debug $debug = null;

	public array $providers_icons = array();

	public function __construct() {
		$this->credentials = new Credentials( $this );
		$this->mode        = new Mode( $this );
		$this->debug       = new Debug( $this );

		$this->id         = 'class_yoco_wc_payment_gateway';
		$this->enabled    = $this->isEnabled();
		$this->has_fields = false;

		$this->icon            = trailingslashit( YOCO_ASSETS_URI ) . 'images/yoco-2024.svg';
		$this->providers_icons = array(
			'Visa'       => trailingslashit( YOCO_ASSETS_URI ) . 'images/visa.svg',
			'MasterCard' => trailingslashit( YOCO_ASSETS_URI ) . 'images/master.svg',
			'MasterPass' => trailingslashit( YOCO_ASSETS_URI ) . 'images/masterpass.svg',
		);

		$this->title       = $this->get_option( 'title', __( 'Yoco', 'yoco_wc_payment_gateway' ) );
		$this->description = $this->get_option( 'description', __( 'Pay securely using a credit/debit card or other payment methods via Yoco.', 'yoco_wc_payment_gateway' ) );

		$this->method_title       = __( 'Yoco Payments', 'yoco_wc_payment_gateway' );
		$this->method_description = __( 'Yoco Payments.', 'yoco_wc_payment_gateway' );

		$this->form_fields = apply_filters( 'yoco_payment_gateway_form_fields', array() );

		// Supported functionality.
		$this->supports = array(
			'products',
			'pre-orders',
			'refunds',
		);

		add_action( "woocommerce_update_options_payment_gateways_{$this->id}", array( $this, 'update_admin_options' ) );
		add_filter( "woocommerce_settings_api_sanitized_fields_{$this->id}", array( $this, 'unset_fields' ) );
	}

	/**
	 * Return the gateway's title.
	 *
	 * @return string
	 */
	public function get_title() {
		$title = is_admin() ? $this->title : '<span class="yoco-payment-method-title">' . $this->title . '</span>';

		return apply_filters( 'woocommerce_gateway_title', $title, $this->id );
	}

	/**
	 * Return the gateway's icon.
	 *
	 * @return string
	 */
	public function get_icon() {

		$icons = '<img class="yoco-payment-method-icon" style="max-height:1em;width:auto;margin-inline-start:1ch;" alt="' . esc_attr( $this->title ) . '" width="100" height="24" src="' . esc_url( $this->icon ) . '"/>';

		$icons .= ! empty( $this->providers_icons ) ? '<span style="float: right;">' : '';

		foreach ( $this->providers_icons as $provider_name => $provider_icon ) {
			$icons .= '<img class="yoco-payment-method-icon" style="max-height:1.2em;width:auto;" alt="' . esc_attr( $provider_name ) . ' logo" width="38" height="24" src="' . esc_url( $provider_icon ) . '"/>';
		}

		$icons .= ! empty( $this->providers_icons ) ? '</span>' : '';

		return apply_filters( 'woocommerce_gateway_icon', $icons, $this->id );
	}

	public function process_payment( $order_id ): ?array {
		$order     = wc_get_order( $order_id );
		$processor = new PaymentProcessor();

		return $processor->process( $order );
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order     = wc_get_order( $order_id );
		$processor = new RefundProcessor();

		return $processor->process( $order, $amount );
	}

	public function update_admin_options() {
		$this->process_admin_options();
	}

	public function unset_fields( $options ) {
		unset( $options['logs'] );

		return $options;
	}

	public function process_admin_options() {
		parent::process_admin_options();

		$processor = new OptionsProcessor( $this );

		return $processor->process();
	}

	public function admin_options() {
		parent::admin_options();

		do_action( 'yoco_payment_gateway/admin/display_notices', $this );

		if ( ! yoco( InstallationsManager::class )->hasInstallationId( $this->get_option( 'mode' ) ) ) {
			yoco( Notices::class )->renderNotice( 'warning', sprintf( __( 'Your gateway is not installed. You must apply and save the plugin %s secrets.', 'yoco_wc_payment_gateway' ), $this->get_option( 'mode' ) ) );
		}
	}

	public function isEnabled(): string {
		return $this->get_option( 'enabled', false );
	}
}
