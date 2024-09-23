<?php

namespace Yoco\Gateway;

use WC_DateTime;
use WC_Order;
use Yoco\Gateway\Payment\Request;
use Yoco\Helpers\Logger;

use function Yoco\yoco;

class PaymentStatusScheduler {

	private const CHECKOUT_ID_ORDER_META_KEY = 'yoco_order_checkout_id';

	private const PAYMENT_ID_ORDER_META_KEY = 'yoco_order_payment_id';

	private const SCHEDULE_INTERVAL = 60;  // seconds.

	private const NO_OF_RETRIES = 30;

	private const MAX_BACKOFF_TIME = 7200;  // 5 days in minutes.

	private const WAIT_TIME_BEFORE_PROCESSING = 10; // minutes.


	public function __construct() {
		add_action( 'yoco_payment_gateway/checkout/created', array( $this, 'order_created' ), 10, 2 );
		add_action( 'yoco_payment_gateway_process_order_payment', array( $this, 'process_list' ) );
		add_action( 'template_redirect', array( $this, 'maybe_update_order_payment_status' ) );
	}

	public function order_created( $order, $payload ) {
		// Add recurring scheduled action if not already added.
		// Scheduled action will run every 60 seconds.
		if ( ! as_has_scheduled_action( 'yoco_payment_gateway_process_order_payment' ) ) {
			as_schedule_recurring_action( time(), self::SCHEDULE_INTERVAL, 'yoco_payment_gateway_process_order_payment', array(), 'yoco', true );
		}

		// Add order to processing list.
		$this->add_order( $order->get_id() );
	}

	public function process_list() {
		$orders_to_process      = get_option( 'yoco_orders_pending_payment', array() );

		if ( empty( $orders_to_process ) || ! is_array( $orders_to_process ) ) {
			return;
		}

		foreach ( $orders_to_process as $order_id => $order_data ) {

			$order = wc_get_order( $order_id );

			if ( ! $order instanceof WC_Order ) {
				yoco( Logger::class )->logError( sprintf( 'Failed to process order payment status update. Can\'t find order #%s', $order_id ) );
				$this->remove_order( $order_id );
				continue;
			}

			// If order has payment ID saved in meta this means payment was successful and we remove order from the list.
			if ( ! empty( $order->get_meta( 'yoco_order_payment_id', true ) ) ) {
				$this->remove_order( $order_id );
				continue;
			}

			// If number of retries exceeds maximum allowed number, log error and remove order from the list.
			if ( $order_data['i'] > self::NO_OF_RETRIES ) {
				// add order note.
				$order->add_order_note(
					sprintf(
						// translators: 1: attempt number, 2: status.
						__( 'Yoco: Failed to process payment after %d attempts', 'yoco_wc_payment_gateway' ),
						$order_data['i']
					)
				);
				yoco( Logger::class )->logError(
					sprintf(
						'Failed to process payment for order: #%1$s after %2$d attempts',
						$order_id,
						$order_data['i']
					)
				);
				$this->remove_order( $order_id );
				continue;
			}

			$now                  = new WC_DateTime( 'now' );
			$next_time_to_process = new WC_DateTime( $order_data['t'] );

			// If time to process is greater than now skip update.
			if ( $next_time_to_process > $now ) {
				continue;
			}

			$request = new Request( $order );

			try {
				$data = $request->get();
				if ( 200 !== $data['code'] ) {
					continue;
				}

				$payment_status = $data['body']['status'];
				$payment_id     = $data['body']['paymentId'];

				// add order note.
				$order->add_order_note(
					sprintf(
						// translators: 1: attempt number, 2: status.
						__( 'Yoco: Payment status update attempt #%1$d -- obtained status: %2$s', 'yoco_wc_payment_gateway' ),
						$order_data['i'],
						$payment_status
					)
				);

				if ( 'completed' === $payment_status && true === $order->update_status( 'processing' ) ) {
					$order->update_meta_data( 'yoco_order_payment_id', $payment_id );
					$order->save_meta_data();

					if ( ! empty( $order->get_meta( 'yoco_order_payment_id', true ) ) ) {
						$this->remove_order( $order->get_id() );
						do_action( 'yoco_payment_gateway/order/meta/yoco_order_payment_id/updated_successfully', $order->get_id() );
					}
					continue;
				}

				$this->update_order( $order_id, $payment_status );

			} catch ( \Throwable $th ) {
				yoco( Logger::class )->logError( sprintf( 'Failed to handle order payment status update. %s', $th->getMessage() ) );
			}
		}
	}

	public function maybe_update_order_payment_status() {

		if ( isset( $_GET['key'] ) && is_order_received_page() ) {
			$order_id = wc_get_order_id_by_order_key( sanitize_key( $_GET['key'] ) );
			if ( 0 === $order_id ) {
				return;
			}
			// When payment capturing process fail.
			if ( isset( $_GET['yoco_checkout_status'] ) && 'failed' === sanitize_key( $_GET['yoco_checkout_status'] ) ) {
				$this->update_order(
					$order_id,
					'failed',
					false
				);

				// Add order note.
				$order = wc_get_order( $order_id );
				if ( $order instanceof WC_Order ) {
					$order->add_order_note( __( 'Yoco: Payment capture failed.', 'yoco_wc_payment_gateway' ) );
				}

				return;
			}

			$this->process_order( $order_id );
		}

		// When payment is canceled.
		if (
			isset( $_GET['key'] )
			&& isset( $_GET['pay_for_order'] )
			&& 'true' === sanitize_key( $_GET['pay_for_order'] )
			&& isset( $_SERVER['HTTP_REFERER'] )
			&& false !== strpos( wp_unslash( $_SERVER['HTTP_REFERER'] ), 'c.yoco' )
		) {
			$order_id = wc_get_order_id_by_order_key( sanitize_key( $_GET['key'] ) );
			if ( 0 === $order_id ) {
				return;
			}

			$this->update_order(
				$order_id,
				'canceled',
				false
			);

			// Add order note.
			$order = wc_get_order( $order_id );
			if ( $order instanceof WC_Order ) {
				$order->add_order_note( __( 'Yoco: Payment canceled by the customer.', 'yoco_wc_payment_gateway' ) );
			}

			// Empty cart when user cancel payment, order is already created.
			// Order can be accessed and paid from My Account page.
			WC()->cart->empty_cart();
		}
	}

	public function process_order( $order_id ) {

		$order = wc_get_order( $order_id );

		/**
		 * WC Order.
		 *
		 * @var WC_Order $order
		*/
		if ( ! $order instanceof WC_Order || 'class_yoco_wc_payment_gateway' !== $order->get_payment_method() ) {
			return;
		}

		// If we have payment ID saved in meta this means payment was successful and we can remove order from the list.
		if ( ! empty( $order->get_meta( 'yoco_order_payment_id', true ) ) ) {
			$this->remove_order( $order->get_id() );
			return;
		}

		$request = new Request( $order );

		try {
			$data = $request->get();
			if ( 200 !== $data['code'] ) {
				return;
			}

			$payment_status = $data['body']['status'];
			$payment_id     = $data['body']['paymentId'];

			if ( 'completed' === $payment_status && true === $order->update_status( 'processing' ) ) {
				$order->update_meta_data( 'yoco_order_payment_id', $payment_id );
				$order->save_meta_data();

				if ( ! empty( $order->get_meta( 'yoco_order_payment_id', true ) ) ) {
					$this->remove_order( $order->get_id() );
					do_action( 'yoco_payment_gateway/order/meta/yoco_order_payment_id/updated_successfully', $order->get_id() );
				}
			}
		} catch ( \Throwable $th ) {
			yoco( Logger::class )->logError( sprintf( 'Failed to handle order payment status update. %s', $th->getMessage() ) );
		}
	}

	/**
	 * Undocumented function
	 *
	 * @param  int $order_id WC Order ID.
	 *
	 * @return void
	 */
	private function add_order( $order_id ) {
		$orders                 = get_option( 'yoco_orders_pending_payment', array() );
		$orders                 = is_array( $orders ) ? $orders : array();
		$hold_stock_minutes     = (int) get_option( 'woocommerce_hold_stock_minutes', 60 );
		$wait_before_processing = $hold_stock_minutes < self::WAIT_TIME_BEFORE_PROCESSING ? intval( $hold_stock_minutes / 2 ) : self::WAIT_TIME_BEFORE_PROCESSING;
		$process_at             = ( new WC_DateTime( 'now + ' . $wait_before_processing . ' min' ) )->__toString();

		if ( ! isset( $orders[ $order_id ] ) ) {
			$orders[ $order_id ] = array(
				't' => $process_at,
				'i' => 1,
				's' => 'init',
			);
			update_option( 'yoco_orders_pending_payment', $orders, false );
		}
	}

	private function remove_order( $order_id ) {
		$orders = get_option( 'yoco_orders_pending_payment', array() );

		if ( is_array( $orders ) ) {
			unset( $orders[ $order_id ] );
		}

		update_option( 'yoco_orders_pending_payment', $orders, false );
	}

	/**
	 * Update order data in order processing list.
	 *
	 * @param  int    $order_id Order payment data.
	 * @param  string $status Payment status.
	 * @param  bool   $increase_counter Counter flag, increase counter by default, prevent increase by passing false.
	 *
	 * @return void
	 */
	private function update_order( $order_id, $status, $increase_counter = true ) {
		$orders = get_option( 'yoco_orders_pending_payment', array() );

		if ( isset( $orders[ $order_id ] ) ) {
			$iteration    = (int) $orders[ $order_id ]['i'];
			$status       = $status ? $status : $orders[ $order_id ]['s'];
			$backoff_time = min( self::MAX_BACKOFF_TIME, $iteration * $iteration * $iteration );
			// In case status is failed|canceled set backoff_time to at least 1440 min (1 day).
			if ( 'failed' === $status || 'canceled' === $status ) {
				$backoff_time = max( $backoff_time, 1440 );
			}
			$orders[ $order_id ] = array(
				't' => ( new WC_DateTime( 'now + ' . $backoff_time . ' min' ) )->__toString(),
				'i' => $increase_counter ? ++$iteration : $iteration,
				's' => $status,
			);
		}

		update_option( 'yoco_orders_pending_payment', $orders, false );
	}

	public function getOrderCheckoutId( WC_Order $order ): string {
		return $this->getOrderMeta( $order, self::CHECKOUT_ID_ORDER_META_KEY );
	}

	public function updateOrderPaymentId( WC_Order $order, string $payment_id ): void {
		$this->updateOrderMeta( $order, self::PAYMENT_ID_ORDER_META_KEY, $payment_id );
	}

	public function getOrderPaymentId( WC_Order $order ): string {
		return $this->getOrderMeta( $order, self::PAYMENT_ID_ORDER_META_KEY );
	}

	public function updateOrderMeta( WC_Order $order, string $key, string $value ): void {
		$order->update_meta_data( $key, $value );
		$order->save_meta_data();
		$action = ! empty( $this->getOrderMeta( $order, $key ) ) ? 'updated_successfully' : 'updated_unsuccessfully';

		do_action( "yoco_payment_gateway/order/meta/{$key}/{$action}", $order->get_id() );
	}

	public function getOrderMeta( WC_Order $order, string $key ): string {
		$meta = $order->get_meta( $key, true, 'yoco' );

		return is_string( $meta ) ? $meta : '';
	}
}
