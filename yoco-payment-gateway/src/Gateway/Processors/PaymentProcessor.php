<?php

namespace Yoco\Gateway\Processors;

use Exception;
use WC_Order;
use Yoco\Gateway\Metadata;
use Yoco\Gateway\Payment\Request;
use Yoco\Helpers\Logger;
use Yoco\Helpers\MoneyFormatter as Money;

use function Yoco\yoco;

class PaymentProcessor {

	public function process( WC_Order $order ): ?array {
		try {
			if ( 200 > yoco( Money::class )->format( $order->get_total() ) ) {
				wc_add_notice( __( 'You must have an order with a minimum of R2,00 to place your order.', 'yoco_wc_payment_gateway' ), 'error' );
				return null;
			}

			$checkoutUrl = yoco( Metadata::class )->getOrderCheckoutUrl( $order );

			if ( ! empty( $checkoutUrl ) ) {
				return $this->createSuccessRedirectResponse( $checkoutUrl );
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

			return $this->createSuccessRedirectResponse( $response['body']['redirectUrl'] );
		} catch ( \Throwable $th ) {
			yoco( Logger::class )->logError( sprintf( 'Yoco: ERROR: Failed to request for payment: "%s".', $th->getMessage() ) );

			wc_add_notice( __( 'Your order could not be processed by Yoco - please try again later.', 'yoco_wc_payment_gateway' ), 'error' );

			return null;
		}
	}

	private function createSuccessRedirectResponse( string $redirectUrl ): array {
		return array(
			'result'   => 'success',
			'redirect' => $redirectUrl,
		);
	}
}
