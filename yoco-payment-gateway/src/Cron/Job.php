<?php

namespace Yoco\Cron;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Job implements JobInterface {

	protected string $action = '';

	public function __construct() {
		add_action( $this->action, array( $this, 'process' ) );
	}
}
