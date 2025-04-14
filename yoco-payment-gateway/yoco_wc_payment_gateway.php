<?php
/**
 * Plugin Name: Yoco Payments
 * Plugin URI: https://wordpress.org/plugins/yoco-payment-gateway/
 * Description: Take debit and credit card payments on your store.
 * Author: Yoco
 * Author URI: https://www.yoco.com
 * Version: 3.8.5
 * Requires at least: 5.0.0
 * Tested up to: 6.8
 * WC requires at least: 8.0.0
 * WC tested up to: 9.8
 * Requires Plugins: woocommerce
 * Text Domain: yoco_wc_payment_gateway
 *
 * @package Yoco Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YOCO_PLUGIN_VERSION', get_file_data( __FILE__, array( 'version' => 'version' ) )['version'] );
define( 'YOCO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'YOCO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'YOCO_ASSETS_PATH', plugin_dir_path( __FILE__ ) . 'assets' );
define( 'YOCO_ASSETS_URI', plugins_url( 'assets', __FILE__ ) );

if ( ! defined( 'YOCO_ONLINE_CHECKOUT_URL' ) ) {
	define( 'YOCO_ONLINE_CHECKOUT_URL', 'https://payments.yoco.com/api/checkouts' );
}
if ( ! defined( 'YOCO_ONLINE_PAYMENT_URL' ) ) {
	define( 'YOCO_ONLINE_PAYMENT_URL', 'https://payments-online.yoco.com/payments' );
}
if ( ! defined( 'YOCO_INSTALL_API_URL' ) ) {
	define( 'YOCO_INSTALL_API_URL', 'https://plugin.yoco.com/installation/woocommerce/createOrUpdate' );
}

use function Yoco\yoco_load;
use function Yoco\yoco;

require dirname( __FILE__ ) . '/inc/autoload.php';

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', YOCO_PLUGIN_BASENAME, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', YOCO_PLUGIN_BASENAME, true );
		}
	}
);

add_action(
	'woocommerce_blocks_loaded',
	function () {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new Yoco\Gateway\BlocksCheckout() );
				}
			);
		}
	}
);

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		yoco_load();
	}
);

/**
 * Display notice if WooCommerce is not installed.
 *
 * @since 3.4.0
 */
add_action(
	'admin_notices',
	function () {
		if ( class_exists( 'WooCommerce' ) ) {
			return;
		}

		echo '<div class="error"><p style="display: flex; gap: 0.5rem; align-items: center;">';
		echo '<img style="height:20px" src="' . esc_url( YOCO_ASSETS_URI ) . '/images/yoco-2024.svg"/>';
		echo '<strong>';
		printf(
			/* translators: %s WooCommerce download URL link. */
			esc_html__( 'Yoco Payment Gateway requires WooCommerce to be installed and active. You can read how to install %s here.', 'yoco_wc_payment_gateway' ),
			'<a href="https://woo.com/document/installing-uninstalling-woocommerce/" target="_blank">WooCommerce</a>'
		);
		echo '</strong></p></div>';
	}
);

register_activation_hook(
	__FILE__,
	function () {
		do_action( 'yoco_payment_gateway/plugin/activated' );
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		do_action( 'yoco_payment_gateway/plugin/deactivated' );
	}
);

add_action(
	'wp_loaded',
	// Maybe update plugin version option.
	function () {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		$version_option_key = 'yoco_wc_payment_gateway_version';
		$installed_version  = get_option( $version_option_key );

		if ( YOCO_PLUGIN_VERSION === $installed_version ) {
			return;
		}

		if ( version_compare( $installed_version, '3.0.0', '<' ) ) {
			$gateway = yoco( \Yoco\Gateway\Provider::class )->getGateway();
			$gateway->update_admin_options();
		}

		update_option( $version_option_key, YOCO_PLUGIN_VERSION );
	}
);
