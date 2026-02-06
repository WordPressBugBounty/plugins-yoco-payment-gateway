<?php

namespace Yoco\Integrations\Yoco\Webhooks\REST;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Route {

	protected string $namespace = 'yoco';
}
