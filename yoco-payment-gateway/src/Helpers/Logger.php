<?php

namespace Yoco\Helpers;

use WC_Log_Handler_File;
use WC_Log_Levels;
use Yoco\Installation\Installation;
use function Yoco\yoco;

class Logger {

	private ?array $error_log_files = null;

	private ?string $error_logs = null;

	public function logError( $message, array $context = array() ): void {
		$this->log( WC_Log_Levels::ERROR, $message, $context );
	}

	public function logInfo( $message, array $context = array() ): void {
		$this->log( WC_Log_Levels::INFO, $message, $context );
	}

	public function getErrorLogs(): string {

		if ( null === $this->error_logs ) {
			if ( empty( $this->getErrorLogFiles() ) ) {
				$this->error_logs = '';
			} else {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				$this->error_logs = '-----BEGIN LOG DATA-----' . PHP_EOL . base64_encode(
					get_site_url() . '/index.php?rest_route=/yoco/logs&file=' . PHP_EOL . array_reduce(
						$this->getErrorLogFiles(),
						function ( $logs, $file_name ) {
							$logs .= $file_name . PHP_EOL;
							return $logs;
						}
					)
				) . PHP_EOL . '------END LOG DATA------';
			}
		}

		return $this->error_logs;
	}

	private function getErrorLogFiles() {
		if ( null === $this->error_log_files ) {
			$this->error_log_files = array_filter(
				WC_Log_Handler_File::get_log_files(),
				function ( $file_name ) {
					return false !== strpos( $file_name, 'yoco-gateway-' ) ? true : false;
				}
			);
		}

		return $this->error_log_files;
	}

	private function isDebugLogEnabled(): bool {
		return ( defined( 'YOCO_DEBUG_LOG' ) && true === YOCO_DEBUG_LOG ) || yoco( Installation::class )->isDebugEnabled();
	}

	private function log( string $level, $message, $context = array() ): void {
		if ( ! $this->isDebugLogEnabled() ) {
			return;
		}

		$context = wp_parse_args(
			$context,
			array(
				'source' => 'yoco-gateway-v' . YOCO_PLUGIN_VERSION . '-' . $level . '-' . yoco( Installation::class )->getMode() . '_mode',
			)
		);

		if ( ! is_scalar( $message ) ) {
			$message = wc_print_r( $message, true );
		}

		$logger = wc_get_logger();
		$logger->log( $level, $message, $context );
	}
}
