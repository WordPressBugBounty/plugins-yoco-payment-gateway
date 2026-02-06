<?php

namespace Yoco\Gateway;

use WC_Order;
use WC_Payment_Gateway;
use WP_Error;
use Yoco\Gateway\Processors\OptionsProcessor;
use Yoco\Gateway\Processors\PaymentProcessor;
use Yoco\Gateway\Processors\RefundProcessor;
use Yoco\Helpers\Admin\Notices;
use Yoco\Helpers\Logger;
use Yoco\Installations\InstallationsManager;

use function Yoco\yoco;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

		$this->icon            = YOCO_ASSETS_URI . '/images/yoco-2024.svg';
		$this->providers_icons = array(
			'Visa'       => YOCO_ASSETS_URI . '/images/visa.svg',
			'MasterCard' => YOCO_ASSETS_URI . '/images/master.svg',
			'MasterPass' => YOCO_ASSETS_URI . '/images/masterpass.svg',
			'Amex'       => YOCO_ASSETS_URI . '/images/american_express.svg',
		);

		$this->title       = $this->get_option( 'title', __( 'Yoco', 'yoco-payment-gateway' ) );
		$this->description = $this->get_option( 'description', __( 'Pay securely using a credit/debit card or other payment methods via Yoco.', 'yoco-payment-gateway' ) );

		$this->method_title       = __( 'Yoco Payments', 'yoco-payment-gateway' );
		$this->method_description = __( 'Yoco Payments.', 'yoco-payment-gateway' );

		$this->form_fields = apply_filters( 'yoco_payment_gateway_form_fields', array() );

		// Supported functionality.
		$this->supports = array(
			'products',
			'pre-orders',
			'refunds',
		);

		add_action( "woocommerce_update_options_payment_gateways_{$this->id}", array( $this, 'update_admin_options' ) );
		add_filter( "woocommerce_settings_api_sanitized_fields_{$this->id}", array( $this, 'unset_fields' ) );

		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'validate_checkout_fields_blocks' ), 10, 2 );

		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout_fields_legacy' ), 10, 2 );
	}

	public function validate_checkout_fields_blocks( $order, $request ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( 'class_yoco_wc_payment_gateway' !== $order->get_payment_method() ) {
			return;
		}

		$first_name = $request['billing_address']['first_name'] ?? '';
		$last_name  = $request['billing_address']['last_name'] ?? '';
		$pattern    = "/^[A-Za-zÀ-ÖØ-öø-ÿ\s\'-]+$/u";

		if ( ! preg_match( $pattern, $first_name ) ) {
			throw new \WC_REST_Exception(
				'billing_first_name_invalid',
				esc_html( $this->get_invalid_chars_message( $first_name, $pattern, 'First name' ) ),
				400
			);
		}

		if ( ! preg_match( $pattern, $last_name ) ) {
			throw new \WC_REST_Exception(
				'billing_last_name_invalid',
				esc_html( $this->get_invalid_chars_message( $last_name, $pattern, 'Last name' ) ),
				400
			);
		}
	}

	public function validate_checkout_fields_legacy( $data, $errors ) {

		$payment_method = $data['payment_method'] ?? '';
		$first_name     = $data['billing_first_name'] ?? '';
		$last_name      = $data['billing_last_name'] ?? '';
		$pattern        = "/^[A-Za-zÀ-ÖØ-öø-ÿ\s\'-]+$/u";

		if ( 'class_yoco_wc_payment_gateway' !== $payment_method ) {
			return;
		}

		if ( ! preg_match( $pattern, $first_name ) ) {
			$errors->add(
				'billing_first_name_invalid',
				$this->get_invalid_chars_message( $first_name, $pattern, __( 'First name', 'yoco-payment-gateway' ) )
			);
		}

		if ( ! preg_match( $pattern, $last_name ) ) {
			$errors->add(
				'billing_last_name_required',
				$this->get_invalid_chars_message( $last_name, $pattern, __( 'Last name', 'yoco-payment-gateway' ) )
			);
		}
	}

	/**
	 * Returns a user-friendly message listing invalid characters in a value.
	 *
	 * @param string $value   The input string to validate.
	 * @param string $pattern Regex pattern allowing valid characters (without delimiters).
	 * @param string $field   Field name for message (e.g., "First name").
	 * @return string|null    Message if invalid characters found, null if valid.
	 */
	private function get_invalid_chars_message( string $value, string $pattern, string $field ): ?string {
		// Remove delimiters and optional anchors.
		$char_pattern = trim( $pattern, '/' );      // removes leading/trailing /.
		$char_pattern = preg_replace( '/^\^/', '', $char_pattern ); // remove starting ^.
		$char_pattern = preg_replace( '/\$$/', '', $char_pattern ); // remove ending $.

		// Remove quantifiers for single-character match.
		$char_pattern = str_replace( '+', '', $char_pattern );
		// Build full regex for allowed characters.
		$allowed_regex = '/' . $char_pattern;

		$invalid_chars = array();
		// Check each character.
		$chars = preg_split( '//u', $value, -1, PREG_SPLIT_NO_EMPTY );
		foreach ( $chars as $char ) {
			if ( ! preg_match( $allowed_regex, $char ) ) {
				$invalid_chars[] = $char;
			}
		}

		if ( ! empty( $invalid_chars ) ) {
			$unique    = array_unique( $invalid_chars );
			$chars_str = implode( ', ', $unique );
			return sprintf(
				/* translators: 1. field name, 2. invalid characters list */
				_n(
					'%1$s field contains invalid character: "%2$s". Please remove it to continue.',
					'%1$s field contains invalid characters: "%2$s". Please remove them to continue.',
					count( $unique ),
					'yoco-payment-gateway'
				),
				$field,
				$chars_str
			);

		}

		return null;
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

	/**
	 * Process payment.
	 *
	 * @param  int $order_id WC_Order ID.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			yoco( Logger::class )->logError(
				sprintf(
					'Can\'t perform payment. Invalid order. Order id: %s',
					$order_id
				)
			);

			return array(
				'result'  => 'failure',
				'message' => __( 'Can\'t perform payment. Invalid order.', 'yoco-payment-gateway' ),
			);
		}

		return PaymentProcessor::process( $order );
	}

	/**
	 * Process refund.
	 *
	 * @param  int        $order_id Order ID.
	 * @param  float|null $amount Refund amount.
	 * @param  string     $reason Refund reason.
	 * @return bool|\WP_Error True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			yoco( Logger::class )->logError(
				sprintf(
					'Can\'t perform refund. Invalid order. Order id: %s',
					$order_id
				)
			);

			return new WP_Error( 'refund_failure', 'Can\'t perform refund. Invalid order.' );
		}

		return RefundProcessor::process( $order, $amount );
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
			// translators: Gateway mode production|test.
			yoco( Notices::class )->renderNotice( 'warning', sprintf( __( 'Your gateway is not installed. You must apply and save the plugin %s secrets.', 'yoco-payment-gateway' ), $this->get_option( 'mode' ) ) );
		}
	}

	public function isEnabled(): string {
		return $this->get_option( 'enabled', false );
	}
}
