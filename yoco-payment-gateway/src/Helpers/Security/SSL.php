<?php

namespace Yoco\Helpers\Security;

class SSL {

	public function isSecure(): bool {
		// cloudflare
		if ( ! empty( $_SERVER['HTTP_CF_VISITOR'] ) ) {
			$cfo = json_decode( $_SERVER['HTTP_CF_VISITOR'] );
			if ( isset( $cfo->scheme ) && 'https' === $cfo->scheme ) {
				return true;
			}
		}

		// other proxy
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
			return true;
		}

		return function_exists( 'is_ssl' ) ? is_ssl() : false;
	}
}
