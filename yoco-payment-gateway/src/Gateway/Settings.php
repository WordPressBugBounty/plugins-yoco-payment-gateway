<?php

namespace Yoco\Gateway;

use WC_Order;
use Yoco\Helpers\Logger;
use Yoco\Core\Setup;
use function Yoco\yoco;

class Settings {

	public function __construct() {
		add_filter( 'yoco_payment_gateway_form_fields', array( $this, 'fields' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'handleThankYou' ) );
	}

	public function fields( array $fields ): array {

		$isDisabled = ! yoco( Setup::class )->deactivateOnIncompatibileEnv();
		$custom     = array(
			'enabled'         => array(
				'title'       => __( 'Enable/Disable', 'yoco_wc_payment_gateway' ),
				'label'       => __( 'Enable Yoco Payments', 'yoco_wc_payment_gateway' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'           => array(
				'title'       => __( 'Title', 'yoco_wc_payment_gateway' ),
				'type'        => 'text',
				'disabled'    => $isDisabled,
				'description' => __( 'Gateway title visible on checkout.', 'yoco_wc_payment_gateway' ),
				'default'     => __( 'Yoco', 'yoco_wc_payment_gateway' ),
			),
			'description'           => array(
				'title'       => __( 'Description', 'yoco_wc_payment_gateway' ),
				'type'        => 'textarea',
				'disabled'    => $isDisabled,
				'description' => __( 'Gateway description visible on checkout.', 'yoco_wc_payment_gateway' ),
				'default'     => __( 'Pay securely using a credit/debit card or other payment methods via Yoco.', 'yoco_wc_payment_gateway' ),
				'css'         => 'max-width:400px;',
			),
			'mode'            => array(
				'title'       => __( 'Mode', 'yoco_wc_payment_gateway' ),
				'label'       => __( 'Mode', 'yoco_wc_payment_gateway' ),
				'type'        => 'select',
				'description' => __( 'Test mode allow you to test the plugin without processing money.<br>Set the plugin to Live mode and click on "Save changes" for real customers to use it.', 'yoco_wc_payment_gateway' ),
				'default'     => 'Test',
				'options'     => array(
					'live' => 'Live',
					'test' => 'Test',
				),
			),
			'live_secret_key' => array(
				'title'       => __( 'Live Secret Key', 'yoco_wc_payment_gateway' ),
				'type'        => 'password',
				'description' => __( 'Live Secret Key', 'yoco_wc_payment_gateway' ),
				'class'       => 'input password-input',
			),
			'test_secret_key' => array(
				'title'       => __( 'Test Secret Key', 'yoco_wc_payment_gateway' ),
				'type'        => 'password',
				'description' => __( 'Test Secret Key', 'yoco_wc_payment_gateway' ),
				'class'       => 'input password-input',
			),
			'debug'           => array(
				'title'       => __( 'Debug', 'yoco_wc_payment_gateway' ),
				'label'       => __( 'Enable logging', 'yoco_wc_payment_gateway' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'yes',
			),
			'logs'            => array(
				'title'             => __( 'Error logs', 'yoco_wc_payment_gateway' ),
				'type'              => 'textarea',
				'description'       => yoco( Logger::class )->getErrorLogs() ? __( 'Click on the field to copy', 'yoco_wc_payment_gateway' ) : '',
				'default'           => yoco( Logger::class )->getErrorLogs(),
				'placeholder'       => yoco( Logger::class )->getErrorLogs() ? '' : __( 'No error logs', 'yoco_wc_payment_gateway' ),
				'custom_attributes' => array(
					'readonly' => 'readonly',
					'onclick'  => yoco( Logger::class )->getErrorLogs() ? 'this.select(); document.execCommand(\'copy\'); this.nextElementSibling.innerText=\'' . __( 'Logs copied', 'yoco_wc_payment_gateway' ) . '\'; this.blur();' : '',
				),
				'css'               => 'padding:2ch;max-width:75ch;height:20ch;font-family:monospace;' . ( yoco( Logger::class )->getErrorLogs() ? 'cursor:copy;' : '' ),
			),
		);

		return array_merge( $fields, $custom );
	}

	public function handleThankYou( int $orderId ) {
		$order = new WC_Order( $orderId );

		if ( ! isset( $_GET['yoco_checkout_status'] ) ) {
			return;
		}

		if ( 'class_yoco_wc_payment_gateway' !== $order->get_payment_method() ) {
			return;
		}

		if ( 'on-hold' !== $order->get_status() ) {
			return;
		}

		$checkoutStatus = $_GET['yoco_checkout_status'];

		if ( 'canceled' === $checkoutStatus ) {
			$order->update_status( 'pending-payment', __( 'Yoco: Checkout session canceled.', 'yoco_wc_payment_gateway' ) );
			wp_safe_redirect( $order->get_checkout_payment_url(), 302, 'Yoco: Redirect canceled payment.' );
			exit;
		}

		if ( 'failed' === $checkoutStatus ) {
			$order->update_status( 'failed', __( 'Yoco: Checkout session failed.', 'yoco_wc_payment_gateway' ) );
			wp_safe_redirect( $order->get_checkout_order_received_url(), 302, 'Yoco: Redirect failed payment,' );
			exit;
		}
	}
}
