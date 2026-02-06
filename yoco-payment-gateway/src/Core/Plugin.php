<?php

namespace Yoco\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	public function __construct() {
		add_action( 'yoco_payment_gateway/plugin/compatibile', array( $this, 'activate' ) );
	}

	public function activate(): void {
		flush_rewrite_rules();
	}
}
