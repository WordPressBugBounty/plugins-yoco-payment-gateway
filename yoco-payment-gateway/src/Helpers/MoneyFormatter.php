<?php

namespace Yoco\Helpers;

class MoneyFormatter {

	public function format( $value, array $options = array() ): int {
		// JR: 16/08/23: Hardcoding decimals to 2 to avoid merchants decimal settings causing issues
		$options = wp_parse_args(
			$options,
			array(
				'decimals'      => 2,
				'rounding_mode' => PHP_ROUND_HALF_UP,
			)
		);

		$decimals      = absint( $options['decimals'] );
		$rounding_mode = min( absint( $options['rounding_mode'] ), 4 );

		return intval( round( ( (float) wc_format_decimal( $value ) ) * ( 10 ** $decimals ), 0, $rounding_mode ) );
	}
}
