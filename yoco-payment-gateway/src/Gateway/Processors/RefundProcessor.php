<?php

namespace Yoco\Gateway\Processors;

use WC_Order;
use WP_Error;
use Yoco\Gateway\Metadata;
use Yoco\Gateway\Refunds\Actions as Refunds_Actions;
use Yoco\Gateway\Refund\Request;
use Yoco\Helpers\Logger;

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
	public function process( WC_Order $order, float $amount ) {

		try {
			Refunds_Actions::sync_refunds( $order );
			$request  = new Request( $order );
			$response = $request->send( $amount );

			$body         = wp_remote_retrieve_body( $response );
			$code         = isset( $response['code'] ) ? (int) $response['code'] : 0;
			$message      = isset( $response['message'] ) ? $response['message'] : '';
			$description  = isset( $body['description'] ) ? $body['description'] : '';
			$full_message = 'Message: ' . $message . ' | Description: ' . $description;
			if ( isset( $body['description'] ) && 'Payment has already been refunded.' !== $body['description'] ) {
				return new WP_Error( $code, $description );
			}

			if ( ( isset( $body['status'] ) && 'succeeded' === $body['status'] ) || 'Payment has already been refunded.' === $body['description'] ) {

				if ( isset( $body['refundId'] ) && yoco( Metadata::class )->getOrderRefundId( $order ) !== $body['refundId'] ) {
					do_action( 'yoco_payment_gateway/order/refunded', $order, $body );
				}

				return true;
			}

			return new WP_Error( $code, $full_message );
		} catch ( \Throwable $th ) {
			yoco( Logger::class )->logError( sprintf( 'Yoco: ERROR: Failed to request for refund: "%s".', $th->getMessage() ) );

			return new WP_Error( $th->getCode(), $th->getMessage() );
		}
	}
}
