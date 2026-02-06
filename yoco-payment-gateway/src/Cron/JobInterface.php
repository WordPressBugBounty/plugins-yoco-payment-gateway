<?php

namespace Yoco\Cron;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface JobInterface {

	public function process( string $mode): void;
}
