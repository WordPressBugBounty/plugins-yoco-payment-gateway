<?php

namespace Yoco\Gateway\Processors;

use Exception;
use WC_Order;
use Yoco\Gateway\Metadata;
use Yoco\Gateway\Payment\Request;
use Yoco\Helpers\Logger;
use Yoco\Helpers\MoneyFormatter as Money;

use function Yoco\yoco;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PaymentProcessor {

	/**
	 * Process payment.
	 *
	 * @param  WC_Order $order Woo Order.
	 *
	 * @return array
	 */
	public static function process( WC_Order $order ): array {
		try {
			if ( 200 > yoco( Money::class )->format( $order->get_total() ) ) {
				// translators: order total.
				wc_add_notice( sprintf( __( 'A minimum order of R2.00 is required to proceed. Your order total is %s.', 'yoco-payment-gateway' ), $order->get_formatted_order_total() ), 'error' );

				return array(
					'result'  => 'failure',
					// translators: order total.
					'message' => sprintf( __( 'A minimum order of R2.00 is required to proceed. Your order total is %s.', 'yoco-payment-gateway' ), $order->get_formatted_order_total() ),
				);
			}

			$checkout_url = yoco( Metadata::class )->getOrderCheckoutUrl( $order );

			if ( ! empty( $checkout_url ) ) {
				return self::create_success_redirect_response( $checkout_url );
			}

			$request  = new Request( $order );
			$response = $request->send();

			if ( ! in_array( (int) $response['code'], array( 200, 201, 202 ), true ) ) {
				$error_message    = isset( $response['body']['errorMessage'] ) ? $response['body']['errorMessage'] : '';
				$error_code       = isset( $response['body']['errorCode'] ) ? $response['body']['errorCode'] : '';
				$response_message = isset( $response['message'] ) ? $response['message'] : '';
				yoco( Logger::class )->logError(
					sprintf(
						'Failed to request checkout. %s',
						$response_message
					) . ( $error_message ? "\n" . $error_message : '' ) . ( $error_code ? "\n" . $error_code : '' )
				);

				throw new Exception( sprintf( 'Failed to request checkout. %s', $response_message ) );
			}

			do_action( 'yoco_payment_gateway/checkout/created', $order, $response['body'] );

			return self::create_success_redirect_response( $response['body']['redirectUrl'] );
		} catch ( \Throwable $th ) {
			yoco( Logger::class )->logError( sprintf( 'Yoco: ERROR: Failed to request for payment: "%s".', $th->getMessage() ) );

			wc_add_notice( __( 'Your order could not be processed by Yoco - please try again later.', 'yoco-payment-gateway' ), 'error' );

			return array(
				'result'  => 'failure',
				'message' => __( 'Your order could not be processed by Yoco - please try again later.', 'yoco-payment-gateway' ),
			);
		}
	}

	/**
	 * Return success result.
	 *
	 * @param  string $url Redirect url.
	 *
	 * @return array
	 */
	private static function create_success_redirect_response( string $url ): array {
		return array(
			'result'   => 'success',
			'redirect' => $url,
		);
	}
}
