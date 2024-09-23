<?php

namespace Yoco\Integrations\Yoco\Webhooks\REST;

use ReflectionClass;
use Yoco\Integrations\Yoco\Webhooks\REST\Routes\Webhook;
use Yoco\Helpers\Logs;

class Router {

	private array $routes = array();

	public function __construct() {
		$this->routes = array(
			'webhook' => Webhook::class,
			'logs'    => Logs::class,
		);

		add_action( 'rest_api_init', array( $this, 'init' ), 11 );
	}

	public function init(): void {
		foreach ( $this->routes as $route ) {
			$reflection = new ReflectionClass( $route );

			$instance = $reflection->newInstance();
			$instance->register();
		}
	}
}
