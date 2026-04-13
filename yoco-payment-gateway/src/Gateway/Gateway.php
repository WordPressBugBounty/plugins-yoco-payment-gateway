<?php

namespace Yoco\Gateway;

use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Exception;
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

		add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'validate_billing_name_chars' ), 5, 1 );

		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout_fields_legacy' ), 10, 2 );
	}

	/**
	 * Server-side billing first/last name validation for the Blocks checkout.
	 *
	 * Hooked on `woocommerce_rest_checkout_process_payment_with_context`, which fires
	 * ONLY inside CheckoutTrait::process_payment() during a POST /wc/store/v1/checkout
	 * (place-order). Throwing an \Exception aborts payment and
	 * CheckoutTrait::process_payment() wraps it into a RouteException with HTTP 400,
	 * esc_html()-ing the message.
	 *
	 * This is intentionally NOT hooked on `woocommerce_store_api_cart_errors` or the
	 * checkout update-order hook: those fire on every cart/checkout response build,
	 * which produces duplicate notices in the UI alongside the per-field errors that
	 * the frontend (public.js) creates on name/gate change. Keeping validation here
	 * means the server only complains at place-order time, which is the last line of
	 * defence in case the JS validation was bypassed.
	 *
	 * @param  PaymentContext $context PaymentContext.
	 * @throws Exception When billing names contain disallowed characters.
	 *
	 * @return void
	 */
	public function validate_billing_name_chars( $context ): void {
		if ( 'class_yoco_wc_payment_gateway' !== $context->payment_method ) {
			return;
		}

		$order = $context->order;
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$first_name = (string) $order->get_billing_first_name();
		$last_name  = (string) $order->get_billing_last_name();
		$pattern    = "/^[A-Za-zÀ-ÖØ-öø-ÿ\s\'-]+$/u";

		$first_invalid = '' !== $first_name ? $this->get_invalid_chars( $first_name, $pattern ) : array();
		$last_invalid  = '' !== $last_name ? $this->get_invalid_chars( $last_name, $pattern ) : array();

		if ( empty( $first_invalid ) && empty( $last_invalid ) ) {
			return;
		}

		$message = $this->build_invalid_name_chars_message( $first_invalid, $last_invalid );

		if ( '' !== $message ) {
			throw new Exception( esc_html( $message ) );
		}
	}

	/**
	 * Build a single user-facing error message that covers any combination of
	 * first/last name having invalid characters.
	 *
	 * Cases:
	 *   - first only:  "First name" field may only contain …
	 *   - last only:   "Last name" field may only contain …
	 *   - both:        "First name" and "Last name" fields may only contain …
	 *
	 * @param string[] $first_invalid Unique invalid chars from the first-name field.
	 * @param string[] $last_invalid  Unique invalid chars from the last-name field.
	 * @return string Composed message, or empty string if no invalid chars were given.
	 */
	private function build_invalid_name_chars_message( array $first_invalid, array $last_invalid ): string {
		$first_has = ! empty( $first_invalid );
		$last_has  = ! empty( $last_invalid );

		if ( ! $first_has && ! $last_has ) {
			return '';
		}

		if ( $first_has && $last_has ) {
			$invalid_chars = array_values( array_unique( array_merge( $first_invalid, $last_invalid ) ) );
			$chars_str     = implode( ', ', $invalid_chars );

			return sprintf(
				/* translators: %s: comma-separated list of invalid characters. */
				__( '"First name" and "Last name" fields may only contain letters, spaces, hyphens, and apostrophes. Please remove: "%s"', 'yoco-payment-gateway' ),
				$chars_str
			);
		}

		$invalid_chars = $first_has ? $first_invalid : $last_invalid;
		$field_label   = $first_has
			? __( 'First name', 'yoco-payment-gateway' )
			: __( 'Last name', 'yoco-payment-gateway' );
		$chars_str     = implode( ', ', $invalid_chars );

		return sprintf(
			/* translators: 1: field label (e.g. "First name"), 2: comma-separated list of invalid characters. */
			__( '"%1$s" field may only contain letters, spaces, hyphens, and apostrophes. Please remove: "%2$s"', 'yoco-payment-gateway' ),
			$field_label,
			$chars_str
		);
	}

	/**
	 * Undocumented function
	 *
	 * @param array    $data   Checkout data.
	 * @param WP_Error $errors WP Error object.
	 *
	 * @return void
	 */
	public function validate_checkout_fields_legacy( $data, $errors ) {

		$payment_method = $data['payment_method'] ?? '';
		$first_name     = $data['billing_first_name'] ?? '';
		$last_name      = $data['billing_last_name'] ?? '';
		$pattern        = "/^[A-Za-zÀ-ÖØ-öø-ÿ\s\'-]+$/u";

		if ( 'class_yoco_wc_payment_gateway' !== $payment_method ) {
			return;
		}

		if ( '' !== $first_name && ! preg_match( $pattern, $first_name ) ) {
			$errors->add(
				'billing_first_name_invalid',
				$this->get_invalid_chars_message( $first_name, $pattern, __( 'First name', 'yoco-payment-gateway' ) )
			);
		}

		if ( '' !== $last_name && ! preg_match( $pattern, $last_name ) ) {
			$errors->add(
				'billing_last_name_invalid',
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
		$invalid_chars = $this->get_invalid_chars( $value, $pattern );

		if ( empty( $invalid_chars ) ) {
			return null;
		}

		$chars_str = implode( ', ', $invalid_chars );
		return sprintf(
			/* translators: 1: field label (e.g. "First name"), 2: comma-separated list of invalid characters. */
			__( '"%1$s" field may only contain letters, spaces, hyphens, and apostrophes. Please remove: "%2$s"', 'yoco-payment-gateway' ),
			$field,
			$chars_str
		);
	}

	/**
	 * Extract the unique invalid characters from a value, given a full regex
	 * pattern that describes the allowed character set.
	 *
	 * @param string $value   The string to inspect.
	 * @param string $pattern Full regex pattern with delimiters and anchors.
	 * @return string[] Unique invalid characters in order of first occurrence.
	 */
	private function get_invalid_chars( string $value, string $pattern ): array {
		// Remove delimiters and optional anchors so we can match a single char.
		$char_pattern  = trim( $pattern, '/' );
		$char_pattern  = preg_replace( '/^\^/', '', $char_pattern );
		$char_pattern  = preg_replace( '/\$$/', '', $char_pattern );
		$char_pattern  = str_replace( '+', '', $char_pattern );
		$allowed_regex = '/' . $char_pattern;

		$invalid = array();
		$chars   = preg_split( '//u', $value, -1, PREG_SPLIT_NO_EMPTY );
		foreach ( $chars as $char ) {
			if ( ! preg_match( $allowed_regex, $char ) ) {
				$invalid[] = $char;
			}
		}

		return array_values( array_unique( $invalid ) );
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
