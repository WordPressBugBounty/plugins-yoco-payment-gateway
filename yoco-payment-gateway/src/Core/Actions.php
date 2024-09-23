<?php

namespace Yoco\Core;

class Actions {

	public function __construct() {
		if ( defined( 'YOCO_PLUGIN_BASENAME' ) && ! empty( YOCO_PLUGIN_BASENAME ) ) {
			add_filter( 'plugin_action_links_' . YOCO_PLUGIN_BASENAME, array( $this, 'setupActionLink' ) );
		}
	}

	public function setupActionLink( array $links ): array {
		if ( ! is_plugin_active( YOCO_PLUGIN_BASENAME ) ) {
			return $links;
		}

		$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=class_yoco_wc_payment_gateway' );
		array_unshift( $links, "<a href=\"{$url}\">" . __( 'Settings', 'yoco_wc_payment_gateway' ) . '</a>' );

		return $links;
	}
}
