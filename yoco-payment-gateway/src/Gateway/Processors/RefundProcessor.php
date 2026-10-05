<?php

namespace Yoco\Gateway\Processors;

use WC_Order;
use WP_Error;
use Yoco\Gateway\Metadata;
use Yoco\Gateway\Refunds\Actions as Refunds_Actions;
use Yoco\Gateway\Refund\Request;
use Yoco\Helpers\Logger;

use function Yoco\yoco;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RefundProcessor {

	/**
	 * Process refund.
	 *
	 * @param  WC_Order   $order Woo Order.
	 * @param  float|null $amount Amount.
	 *
	 * @return bool|WP_Error
	 */
	public static function process( WC_Order $order, float $amount ) {

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
				return self::refund_failed( $order, $amount, new WP_Error( $code, $description ) );
			}

			if ( ( isset( $body['status'] ) && 'succeeded' === $body['status'] ) || 'Payment has already been refunded.' === $body['description'] ) {

				if ( isset( $body['refundId'] ) && yoco( Metadata::class )->getOrderRefundId( $order ) !== $body['refundId'] ) {
					do_action( 'yoco_payment_gateway/order/refunded', $order, $body );
				}

				return true;
			}

			return self::refund_failed( $order, $amount, new WP_Error( $code, $full_message ) );
		} catch ( \Throwable $th ) {
			// WP_Error drops the message when the code is empty, and these exceptions carry code 0.
			return self::refund_failed( $order, $amount, new WP_Error( 'yoco_refund_failed', $th->getMessage() ) );
		}
	}

	/**
	 * Record a failed refund in the gateway log and the order notes.
	 *
	 * @param  WC_Order $order Woo Order.
	 * @param  float    $amount Amount.
	 * @param  WP_Error $error Error returned to WooCommerce.
	 *
	 * @return WP_Error The same error.
	 */
	private static function refund_failed( WC_Order $order, float $amount, WP_Error $error ): WP_Error {
		yoco( Logger::class )->logError( sprintf( 'Refund of %1$s for order #%2$s failed: %3$s', $amount, $order->get_id(), $error->get_error_message() ) );

		$order->add_order_note(
			sprintf(
				// translators: 1: refund amount, 2: error message.
				__( 'Yoco: Refund of %1$s failed. %2$s', 'yoco-payment-gateway' ),
				wc_price( $amount, array( 'currency' => $order->get_currency() ) ),
				$error->get_error_message()
			)
		);

		return $error;
	}
}
