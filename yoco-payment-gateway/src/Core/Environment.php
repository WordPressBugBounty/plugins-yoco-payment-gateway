<?php

namespace Yoco\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Environment {

	public function isDevelopmentEnvironment(): bool {
		return defined( 'YOCO_DEBUG_ENV' ) && true === YOCO_DEBUG_ENV;
	}
}
