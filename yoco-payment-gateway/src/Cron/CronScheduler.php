<?php

namespace Yoco\Cron;

use Yoco\Helpers\Logger;

use function Yoco\yoco;

class CronScheduler {

	public function scheduleEvent( string $action, array $args = array(), int $timestamp = 0 ): void {
		if ( 0 === $timestamp ) {
			$timestamp = $this->getTimestamp();
		}

		/**
		 * @var bool|\WP_Error $scheduled
		 */
		$scheduled = wp_schedule_single_event( $timestamp, $action, $args );

		if ( is_wp_error( $scheduled ) ) {
			yoco( Logger::class )->logError( $scheduled->get_error_message() );
		}
	}

	private function getTimestamp(): int {
		return time() + 5 * MINUTE_IN_SECONDS;
	}
}
