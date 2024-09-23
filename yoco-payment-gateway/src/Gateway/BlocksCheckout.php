<?php

namespace Yoco\Gateway;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Yoco payment method integration
 *
 * @since 3.4.0
 */
final class BlocksCheckout extends AbstractPaymentMethodType {
	/**
	 * Name of the payment method.
	 *
	 * @var string
	 */
	protected $name = 'class_yoco_wc_payment_gateway';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_class_yoco_wc_payment_gateway_settings', array() );
	}

	/**
	 * Returns if this payment method is active. If false, the scripts will not be enqueued.
	 *
	 * @return bool
	 */
	public function is_active() {
		$payment_gateways = WC()->payment_gateways()->payment_gateways();

		return isset( $payment_gateways['class_yoco_wc_payment_gateway'] )
			&& $payment_gateways['class_yoco_wc_payment_gateway'] instanceof \WC_Payment_Gateway
			&& $payment_gateways['class_yoco_wc_payment_gateway']->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'yoco-blocks-integration',
			YOCO_ASSETS_URI . '/scripts/public.js',
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			),
			YOCO_PLUGIN_VERSION,
			true
		);
		wp_set_script_translations(
			'yoco-blocks-integration',
			'yoco_wc_payment_gateway'
		);
		return array( 'yoco-blocks-integration' );
	}

	/**
	 * Returns data available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => $this->get_supported_features(),
			'logo_url'    => YOCO_ASSETS_URI . '/images/yoco-2024.svg',
		);
	}

	/**
	 * Returns an array of supported features.
	 *
	 * @return string[]
	 */
	public function get_supported_features() {
		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		if (
			! isset( $payment_gateways['class_yoco_wc_payment_gateway'] )
			|| ! $payment_gateways['class_yoco_wc_payment_gateway'] instanceof \WC_Payment_Gateway
			|| ! isset( $payment_gateways['class_yoco_wc_payment_gateway']->supports )
		) {
			return array();
		}
		return $payment_gateways['class_yoco_wc_payment_gateway']->supports;
	}
}
