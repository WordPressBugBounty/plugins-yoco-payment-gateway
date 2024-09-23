<?php

namespace Yoco\Helpers\Admin;

class Notices {

	private array $types = array(
		'warning' => 'notice-warning',
		'error'   => 'notice-error',
		'info'    => 'notice-info',
		'success' => 'notice-success',
	);

	private function hasNoticeType( string $type ): bool {
		return array_key_exists( $type, $this->types );
	}

	private function getNoticeType( string $type ): string {
		return $this->types[ $type ];
	}

	public function displayAdminNotice( string $type, string $message ): void {
		if ( ! $this->hasNoticeType( $type ) ) {
			return;
		}

		$type = $this->getNoticeType( $type );

		add_action(
			'admin_notices',
			function () use ( $type, $message ) {
				echo "<div class=\"notice {$type} is-dismissible\"><p>{$message}</p></div>";
			}
		);
	}

	public function renderNotice( string $type, string $message ): void {
		static $notices;

		if ( ! is_admin() || ! $this->hasNoticeType( $type ) ) {
			return;
		}

		$key = md5( $type . $message );
		if ( isset( $notices[ $key ] ) ) {
			return;
		}

		$message = str_replace("\n", '<br>', $message );
		$prefix          = apply_filters( 'yoco_payment_gateway_admin_notice_prefix', __( 'Yoco Payments', 'yoco_wc_payment_gateway' ) );
		$type            = $this->getNoticeType( $type );
		$notices[ $key ] = true;

		add_action(
			'admin_notices',
			function () use ( $type, $prefix, $message ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<div class="yoco-notice notice ', esc_html( $type ), ' is-dismissible"><p><b>', esc_html( $prefix ), ':</b> ', force_balance_tags( $message ), '</p></div>';
			}
		);
	}
}
