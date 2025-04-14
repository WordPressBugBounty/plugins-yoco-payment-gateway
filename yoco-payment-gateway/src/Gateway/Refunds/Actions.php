<?php

namespace Yoco\Gateway\Refunds;

use WC_Order;
use Yoco\Gateway\Refunds\Request;
use Yoco\Gateway\Metadata;
use Yoco\Helpers\Logger;
use Yoco\Helpers\MoneyFormatter as Money;

use function Yoco\yoco;

class Actions {

	public static function sync_refunds( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$wc_refunds = $order->get_refunds();

		$request = new Request( $order );

		try {
			$refunds_responce = $request->get();

			/**
			 * Refunds response.
			 *
			 * @var array{refunds: array<int, object>} $body
			 */
			$body = wp_remote_retrieve_body( $refunds_responce );

			if ( ! isset( $body['refunds'] ) ) {
				return;
			}

			if ( ! is_array( $body['refunds'] ) ) {
				return;
			}

			/**
			 * Yoco Payment Refunds Array.
			 *
			 * @var array $refunds
			 */
			$refunds = $body['refunds'];
		} catch ( \Throwable $th ) {
			yoco( Logger::class )->logError( sprintf( 'Failed to sync refunds. %s', $th->getMessage() ) );
			return;
		}

		// Remove already synced refunds from $refunds array.
		foreach ( $wc_refunds as $wc_refund ) {
			$refund_id = yoco( Metadata::class )->getOrderRefundId( $wc_refund );

			if ( ! $refund_id ) {
				continue;
			}

			foreach ( $refunds as $key => $refund ) {
				if ( ! isset( $refund->id ) ) {
					continue;
				}

				if ( $refund_id === $refund->id ) {
					unset( $refunds[ $key ] );
				}
			}
		}

		// Try to match remaining refunds in $refund array with local WooCommerce refunds and store Refund ID.
		foreach ( $wc_refunds as $wc_refund ) {
			$refund_id = yoco( Metadata::class )->getOrderRefundId( $wc_refund );

			if ( false !== strpos( $refund_id, 'rfd' ) ) {
				continue;
			}

			foreach ( $refunds as $key => $refund ) {
				if (
					! isset( $refund->id )
					|| ! isset( $refund->amount )
					|| ! isset( $refund->status )
				) {
					continue;
				}

				if (
					'succeeded' === $refund->status
					&& yoco( Money::class )->format( $wc_refund->get_amount() ) === $refund->amount
				) {
					yoco( Metadata::class )->updateOrderRefundId( $wc_refund, array( 'refundId' => $refund->id ) );
					unset( $refunds[ $key ] );
				}
			}
		}

		// Create all remaining refunds in WooCommerce and store Refund ID.
		foreach ( $refunds as $refund ) {
			if (
				! isset( $refund->id )
				|| ! isset( $refund->amount )
				|| ! isset( $refund->status )
				|| 'succeeded' !== $refund->status
			) {
				continue;
			}

			$args = array(
				'amount'         => $refund->amount / 100,
				'reason'         => sprintf(
					__( 'Refund requested via Yoco Portal. Refund ID (%s)', 'yoco_wc_payment_gateway' ),
					$refund->id
				),
				'order_id'       => $order->get_id(),
				'refund_payment' => false,
			);

			$new_refund = wc_create_refund( apply_filters( 'yoco_payment_gateway/request/refund/args', $args ) );

			if ( is_wp_error( $new_refund ) ) {
				yoco( Logger::class )->logError( 'Refund creation failed: ' . $new_refund->get_error_message() . ' code: ' . $new_refund->get_error_code() );
				return;
			}

			do_action( 'yoco_payment_gateway/order/refunded', $new_refund, array( 'refundId' => $refund->id ) );
			do_action( 'yoco_payment_gateway/order/refunded', $order, array( 'refundId' => $refund->id ) );
		}
	}
}
