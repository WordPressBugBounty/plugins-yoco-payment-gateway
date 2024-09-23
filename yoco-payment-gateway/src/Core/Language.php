<?php

namespace Yoco\Core;

use Yoco\Helpers\Admin\Notices;
use Yoco\Helpers\Logger;

use function Yoco\yoco;

class Language {

	public const DOMAIN_NAME = 'yoco_wc_payment_gateway';

	public function __construct() {
		add_action( 'init', array( $this, 'loadPluginTextDomain' ) );
	}

	public function loadPluginTextDomain(): void {
		$path = trailingslashit( basename( YOCO_PLUGIN_PATH ) ) . 'assets/lang';

		if ( false === load_plugin_textdomain( 'yoco_wc_payment_gateway', false, $path ) ) {
			yoco( Notices::class )->renderNotice( 'error', __( 'Failed to load plugin textdomain.', 'yoco_wc_payment_gateway' ) );
			yoco( Logger::class )->logError( 'Failed to load plugin textdomain.' );
		}
	}
}
