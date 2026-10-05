<?php

namespace Yoco\Installation;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thrown when no secret key is stored for the requested mode.
 *
 * @since 3.9.5
 */
class MissingSecretKeyException extends Exception {}
