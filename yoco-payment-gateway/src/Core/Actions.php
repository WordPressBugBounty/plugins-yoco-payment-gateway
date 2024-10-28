<?php

namespace Yoco\Core;

use Yoco\Gateway\Refunds\Actions as Refunds_Actions;

class Actions {

	public function __construct() {
		if ( defined( 'YOCO_PLUGIN_BASENAME' ) && ! empty( YOCO_PLUGIN_BASENAME ) ) {
			add_filter( 'plugin_action_links_' . YOCO_PLUGIN_BASENAME, array( $this, 'setupActionLink' ) );
		}
		add_filter( 'woocommerce_order_actions', array( $this, 'register_sync_refunds_action' ) );
		add_action( 'woocommerce_order_action_yoco_sync_refunds', array( $this, 'handle_sync_refunds_action' ) );
	}

	public function setupActionLink( array $links ): array {
		if ( ! is_plugin_active( YOCO_PLUGIN_BASENAME ) ) {
			return $links;
		}

		$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=class_yoco_wc_payment_gateway' );
		array_unshift( $links, "<a href=\"{$url}\">" . __( 'Settings', 'yoco_wc_payment_gateway' ) . '</a>' );

		return $links;
	}

	public function register_sync_refunds_action( $actions ) {
		$actions['yoco_sync_refunds'] = __( 'Yoco: Sync Refunds', 'yoco_wc_payment_gateway' );

		return $actions;
	}

	public function handle_sync_refunds_action( $order ) {
		Refunds_Actions::sync_refunds( $order );
	}
}
