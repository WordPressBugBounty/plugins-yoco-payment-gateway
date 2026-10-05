<?php

namespace Yoco\Helpers\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSL {

	public function isSecure(): bool {
		// cloudflare, WordPress slashes $_SERVER so unslash before decoding.
		if ( ! empty( $_SERVER['HTTP_CF_VISITOR'] ) ) {
			$visitor = json_decode( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_VISITOR'] ) ) );
			if ( isset( $visitor->scheme ) && 'https' === $visitor->scheme ) {
				return true;
			}
		}

		// other proxy
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) ) ) {
			return true;
		}

		return function_exists( 'is_ssl' ) ? is_ssl() : false;
	}
}
