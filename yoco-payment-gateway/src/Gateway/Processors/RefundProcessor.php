<?php

namespace Yoco\Gateway\Processors;

use WC_Order;
use WP_Error;
use Yoco\Gateway\Refund\Request;
use Yoco\Helpers\Logger;
use Yoco\Helpers\MoneyFormatter as Money;

use function Yoco\yoco;

class RefundProcessor {

	/**
	 * Process refund.
	 *
	 * @param  WC_Order   $order
	 * @param  float|null $amount
	 * @param  string     $reason
	 *
	 * @return bool|WP_Error
	 */
	public function process( WC_Order $order, ?float $amount, string $reason ) {
		if ( 0 !== yoco( Money::class )->format( $order->get_remaining_refund_amount() ) ) {
			return new WP_Error( 400, __( 'Refund failed. Only full refund allowed.', 'yoco_wc_payment_gateway' ) );
		}

		try {
			$request  = new Request( $order );
			$response = $request->send();

			if ( isset( $response['body']['description'] ) ) {
				return new WP_Error( 400, $response['body']['description'] );
			}

			if ( isset( $response['body']['status'] ) && 'successful' === $response['body']['status'] ) {
				do_action( 'yoco_payment_gateway/order/refunded', $order, $response['body'] );
			}

			return new WP_Error( 200, $response['body']['message'] ?? '' );
		} catch ( \Throwable $th ) {
			yoco( Logger::class )->logError( sprintf( 'Yoco: ERROR: Failed to request for refund: "%s".', $th->getMessage() ) );

			return new WP_Error( $th->getCode(), $th->getMessage() );
		}
	}
}
