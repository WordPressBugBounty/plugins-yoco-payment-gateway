<?php

namespace Yoco\Helpers;

use Yoco\Core\Environment;

use function Yoco\yoco;

class Versioner {

	public static function getDependenciesVersion(): string {
		if ( yoco( Environment::class )->isDevelopmentEnvironment() ) {
			return (string) time();
		}

		if ( defined( 'YOCO_PLUGIN_VERSION' ) ) {
			return YOCO_PLUGIN_VERSION;
		}

		return '';
	}
}
