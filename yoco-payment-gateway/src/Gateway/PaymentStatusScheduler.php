<?php

namespace Yoco\Gateway;

use WC_DateTime;
use WC_Order;
use Yoco\Gateway\Metadata;
use Yoco\Gateway\Payment\Request;
use Yoco\Helpers\Http\Client;
use Yoco\Helpers\Logger;
use Yoco\Installation\MissingSecretKeyException;

use function Yoco\yoco;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PaymentStatusScheduler {

	private const SCHEDULE_INTERVAL = 60;  // seconds.

	private const NO_OF_RETRIES = 30;

	private const MAX_BACKOFF_TIME = 7200;  // 5 days in minutes.

	private const WAIT_TIME_BEFORE_PROCESSING = 10; // minutes.

	private const CANCELLED_GRACE_PERIOD = 1440; // minutes, the Yoco checkout stays payable after WooCommerce cancels the order.

	public function __construct() {
		add_action( 'yoco_payment_gateway/checkout/created', array( $this, 'order_created' ), 10, 2 );
		add_action( 'yoco_payment_gateway_process_order_payment', array( $this, 'process_list' ) );
		add_action( 'template_redirect', array( $this, 'maybe_update_order_payment_status' ) );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'order_cancelled' ), 10, 2 );
		add_action( 'update_option_woocommerce_class_yoco_wc_payment_gateway_settings', array( $this, 'settings_updated' ), 10, 2 );
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

	/**
	 * Keep polling a cancelled Yoco order for the grace period, its checkout stays payable.
	 *
	 * Hooked to `woocommerce_order_status_cancelled`.
	 *
	 * @param  int           $order_id WC Order ID.
	 * @param  WC_Order|null $order WC Order when WooCommerce passes it.
	 *
	 * @return void
	 *
	 * @since 3.9.5
	 */
	public function order_cancelled( $order_id, $order = null ): void {
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order || 'class_yoco_wc_payment_gateway' !== $order->get_payment_method() ) {
			return;
		}

		// The Yoco checkout stays open after the cancel, keep polling for a while so a late payment is still recorded.
		$this->mark_order_cancelled( $order->get_id() );
	}

	/**
	 * Make every queued order due on the next run once a secret key changes.
	 *
	 * Hooked to `update_option_woocommerce_class_yoco_wc_payment_gateway_settings`.
	 *
	 * @param  mixed $old_value Settings before the save.
	 * @param  mixed $value Settings after the save.
	 *
	 * @return void
	 *
	 * @since 3.9.5
	 */
	public function settings_updated( $old_value, $value ): void {
		$old_value = is_array( $old_value ) ? $old_value : array();
		$value     = is_array( $value ) ? $value : array();

		foreach ( array( 'live_secret_key', 'test_secret_key' ) as $key ) {
			if ( ( $old_value[ $key ] ?? '' ) !== ( $value[ $key ] ?? '' ) ) {
				// Credentials changed, poll every queued order on the next run instead of waiting out the backoff.
				$this->reschedule_all();
				return;
			}
		}
	}

	public function process_list() {
		$orders_to_process = get_option( 'yoco_orders_pending_payment', array() );

		if ( empty( $orders_to_process ) || ! is_array( $orders_to_process ) ) {
			return;
		}

		foreach ( $orders_to_process as $order_id => $order_data ) {
			$this->process_queued_order( $order_id, $order_data );
		}
	}

	public function maybe_update_order_payment_status() {
		// if webhook is running bail.
		if ( get_transient( 'yoco_webhook_processing' ) ) {
			return;
		}

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
					$order->add_order_note( __( 'Yoco: Payment capture failed.', 'yoco-payment-gateway' ) );
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
				$order->add_order_note( __( 'Yoco: Payment canceled by the customer.', 'yoco-payment-gateway' ) );
			}

			// Empty cart when user cancel payment, order is already created.
			// Order can be accessed and paid from My Account page.
			WC()->cart->empty_cart();
		}
	}

	public function process_order( $order_id ) {
		// The order-received page can load twice in a row, the second load must not complete the payment again.
		if ( get_transient( 'yoco_order_processing_' . $order_id ) ) {
			return;
		}

		set_transient( 'yoco_order_processing_' . $order_id, true, 10 );
		$order = wc_get_order( $order_id );

		/**
		 * WC Order.
		 *
		 * @var WC_Order $order
		*/
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		// If somehow we got order with payment method other than Yoco or without Checkout ID remove order from the list.
		if (
			'class_yoco_wc_payment_gateway' !== $order->get_payment_method()
			|| empty( yoco( Metadata::class )->getOrderCheckoutId( $order ) )
		) {
			$this->remove_order( $order->get_id() );
			delete_transient( 'yoco_order_processing_' . $order->get_id() );
			return;
		}

		// If we have payment ID saved in meta this means payment was successful and we can remove order from the list.
		if ( ! empty( yoco( Metadata::class )->getOrderPaymentId( $order ) ) ) {
			$this->remove_order( $order->get_id() );

			delete_transient( 'yoco_order_processing_' . $order->get_id() );
			return;
		}

		// Cancelled orders are checked on purpose, the customer has just returned from Yoco.
		// A late payment completes the order here the same way the webhook does.
		$request = new Request( $order );

		try {
			$data = $request->get();
			$code = (int) $data['code'];
			if ( 200 !== $code ) {
				yoco( Logger::class )->logError(
					sprintf(
						'Payment status request for order #%1$s failed with HTTP %2$d %3$s.',
						$order->get_id(),
						$code,
						$data['message'] ?? ''
					)
				);

				if ( in_array( $code, Client::UNAUTHORIZED_STATUS_CODES, true ) ) {
					$this->add_unauthorized_order_note( $order );
				}

				delete_transient( 'yoco_order_processing_' . $order->get_id() );
				return;
			}

			$payment_status = $data['body']['status'];
			$payment_id     = $data['body']['paymentId'];

			if ( 'completed' === $payment_status && $this->complete_payment( $order, (string) $payment_id ) ) {
				$this->remove_order( $order->get_id() );
			}

			delete_transient( 'yoco_order_processing_' . $order->get_id() );
		} catch ( \Throwable $th ) {
			yoco( Logger::class )->logError( sprintf( 'Failed to handle payment status update for order #%1$s. %2$s', $order->get_id(), $th->getMessage() ) );
			delete_transient( 'yoco_order_processing_' . $order->get_id() );
		}
	}

	/**
	 * Run one scheduled status check for a queued order.
	 *
	 * @param  int   $order_id WC Order ID.
	 * @param  array $order_data Order payment data.
	 *
	 * @return void
	 */
	private function process_queued_order( $order_id, array $order_data ): void {
		$order = $this->load_queued_order( $order_id, $order_data );

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		// If time to process is greater than now skip update.
		// Inside the grace period update_order() never schedules past its end, so the last check lands on time.
		if ( new WC_DateTime( $order_data['t'] ) > new WC_DateTime( 'now' ) ) {
			return;
		}

		// Once the grace period is over a cancelled order leaves the queue on the first answer that is not completed.
		$final_check = $order->has_status( 'cancelled' ) && $this->cancelled_grace_elapsed( $order, $order_data );

		try {
			$data   = ( new Request( $order ) )->get();
			$code   = (int) $data['code'];
			$status = is_array( $data['body'] ?? null ) ? ( $data['body']['status'] ?? '' ) : '';

			if ( 200 !== $code ) {
				// A failed request is not an answer, even on the final check: it backs off, bounded by the retry cap.
				$this->handle_failed_status_request( $order, $order_data, $data );
			} elseif ( ! is_string( $status ) || '' === $status ) {
				// Neither is a 200 without a status, such as a proxy or maintenance page.
				yoco( Logger::class )->logError( sprintf( 'Payment status response for order #%1$s has no status (attempt %2$d).', $order_id, (int) $order_data['i'] ) );
				$this->update_order( $order_id );
			} elseif ( $final_check && 'completed' !== $status ) {
				$this->stop_polling_cancelled_order( $order, $status );
			} else {
				$this->handle_status_response( $order, $order_data, $data['body'] );
			}
		} catch ( MissingSecretKeyException $e ) {
			// The logger is silent when debug is off, so the note is the only sign of a missing key.
			$this->add_missing_key_order_note( $order, $e->getMessage() );
			$this->update_order( $order_id );
		} catch ( \Throwable $th ) {
			yoco( Logger::class )->logError( sprintf( 'Failed to handle payment status update for order #%1$s. %2$s', $order_id, $th->getMessage() ) );
			// Apply backoff so the order is not retried on every run.
			$this->update_order( $order_id );
		}
	}

	/**
	 * Load a queued order, dropping entries that no longer need polling.
	 *
	 * @param  int   $order_id WC Order ID.
	 * @param  array $order_data Order payment data.
	 *
	 * @return WC_Order|null
	 */
	private function load_queued_order( $order_id, array $order_data ): ?WC_Order {
		$order = wc_get_order( $order_id );
		$drop  = false;

		if ( ! $order instanceof WC_Order ) {
			yoco( Logger::class )->logError( sprintf( 'Failed to process order payment status update. Can\'t find order #%s', $order_id ) );
			$drop = true;
		} elseif ( empty( yoco( Metadata::class )->getOrderCheckoutId( $order ) ) ) {
			yoco( Logger::class )->logError( sprintf( 'Failed to process order payment. Order #%s is missing Checkout ID.', $order_id ) );
			$drop = true;
		} elseif ( ! empty( yoco( Metadata::class )->getOrderPaymentId( $order ) ) ) {
			// If order has payment ID saved in meta this means payment was successful and we remove order from the list.
			$drop = true;
		} elseif ( $order_data['i'] > self::NO_OF_RETRIES ) {
			// If number of retries exceeds maximum allowed number, log error and remove order from the list.
			// Cancelled orders are not exempt: this cap ends the checks of a cancelled order that Yoco keeps
			// not answering, or whose completed payment WooCommerce keeps refusing to save.
			$order->add_order_note(
				sprintf(
					// translators: %d: number of attempts.
					__( 'Yoco: Failed to process payment after %d attempts', 'yoco-payment-gateway' ),
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
			$drop = true;
		}

		if ( $drop ) {
			$this->remove_order( $order_id );
			return null;
		}

		return $order;
	}

	/**
	 * Apply a successful status response to the order and its queue entry.
	 *
	 * @param  WC_Order $order WC Order.
	 * @param  array    $order_data Order payment data.
	 * @param  array    $body Response body.
	 *
	 * @return void
	 */
	private function handle_status_response( WC_Order $order, array $order_data, array $body ): void {
		$payment_status = $body['status'];
		$payment_id     = $body['paymentId'];

		$order->add_order_note(
			sprintf(
				// translators: 1: attempt number, 2: status.
				__( 'Yoco: Payment status update attempt #%1$d -- obtained status: %2$s', 'yoco-payment-gateway' ),
				$order_data['i'],
				$payment_status
			)
		);

		if ( 'completed' !== $payment_status ) {
			$this->update_order( $order->get_id(), $payment_status );
			return;
		}

		if ( ! $this->complete_payment( $order, (string) $payment_id ) ) {
			// WooCommerce could not save the payment, keep the order queued so the next run retries.
			$this->update_order( $order->get_id(), $payment_status );
			return;
		}

		$this->remove_order( $order->get_id() );
	}

	/**
	 * Mark the order as paid for a completed Yoco payment and store the payment ID.
	 *
	 * The ID is stored before the completed action runs, so a listener that throws cannot lose it.
	 *
	 * @param  WC_Order $order WC Order.
	 * @param  string   $payment_id Yoco payment ID.
	 *
	 * @return bool False when WooCommerce could not save the payment, true otherwise.
	 */
	private function complete_payment( WC_Order $order, string $payment_id ): bool {
		// The paid date alone is not enough, WooCommerce keeps it when an order is moved back to an unpaid status.
		if ( ! $order->is_paid() && true !== $order->payment_complete( $payment_id ) ) {
			return false;
		}

		if ( ! $order->is_paid() || empty( $order->get_date_paid() ) ) {
			// payment_complete() returns true without marking the order paid when its status does not accept payment.
			// Retrying cannot change that, so keep the payment id and tell the merchant instead.
			yoco( Logger::class )->logError( sprintf( 'Order #%1$s was not marked paid for completed Yoco payment %2$s, its status %3$s does not accept payment.', $order->get_id(), $payment_id, $order->get_status() ) );
			$order->add_order_note(
				sprintf(
					// translators: 1: Yoco payment ID, 2: order status name.
					__( 'Yoco: Payment %1$s is completed, but the order was not marked paid because its status (%2$s) does not accept payment. Please check the order.', 'yoco-payment-gateway' ),
					$payment_id,
					wc_get_order_status_name( $order->get_status() )
				)
			);
		}

		$metadata = yoco( Metadata::class );

		// An order that already carries the id had the action fired on an earlier run.
		if ( empty( $metadata->getOrderPaymentId( $order ) ) ) {
			$metadata->updateOrderPaymentId( $order, $payment_id );

			/**
			 * Fires an action hook after a Yoco payment has been completed for an order.
			 *
			 * @param WC_Order $order       The order object.
			 * @param string   $payment_id The ID of the completed Yoco payment.
			 *
			 * @since 1.0.0
			 */
			do_action( 'yoco_payment_gateway/payment/completed', $order, $payment_id );
		}

		return true;
	}

	/**
	 * Tell whether a cancelled order has been out of its polling grace period.
	 *
	 * @param  WC_Order $order WC Order.
	 * @param  array    $order_data Order payment data.
	 *
	 * @return bool
	 */
	private function cancelled_grace_elapsed( WC_Order $order, array $order_data ): bool {
		$grace_period_end = $this->grace_period_end( $order_data );

		if ( $grace_period_end ) {
			return $grace_period_end <= new WC_DateTime( 'now' );
		}

		// Entries queued before the cancellation time was recorded fall back to the last modification.
		$cancelled_at = $order->get_date_modified();

		if ( ! $cancelled_at instanceof \DateTimeInterface ) {
			return false;
		}

		return $cancelled_at < new WC_DateTime( 'now - ' . self::CANCELLED_GRACE_PERIOD . ' min' );
	}

	/**
	 * Drop a cancelled order from the queue after its last status check.
	 *
	 * @param  WC_Order $order WC Order.
	 * @param  string   $payment_status Payment status the last check returned.
	 *
	 * @return void
	 */
	private function stop_polling_cancelled_order( WC_Order $order, string $payment_status ): void {
		$order->add_order_note(
			sprintf(
				// translators: 1: grace period in hours, 2: payment status.
				__( 'Yoco: Payment status checks stopped. The order was cancelled more than %1$d hours ago and the last check returned %2$s.', 'yoco-payment-gateway' ),
				self::CANCELLED_GRACE_PERIOD / 60,
				$payment_status
			)
		);
		yoco( Logger::class )->logError( sprintf( 'Stopped polling cancelled order #%1$s after the grace period, the last check returned %2$s.', $order->get_id(), $payment_status ) );
		$this->remove_order( $order->get_id() );
	}

	/**
	 * Get the end of a cancelled order's grace period from its queue entry.
	 *
	 * @param  array $order_data Order payment data.
	 *
	 * @return WC_DateTime|null Null when the entry has no cancellation time.
	 */
	private function grace_period_end( array $order_data ): ?WC_DateTime {
		if ( empty( $order_data['c'] ) ) {
			return null;
		}

		$grace_period_end = new WC_DateTime( $order_data['c'] );
		$grace_period_end->modify( '+' . self::CANCELLED_GRACE_PERIOD . ' minutes' );

		return $grace_period_end;
	}

	/**
	 * Record the cancellation time on the order's queue entry.
	 *
	 * @param  int $order_id WC Order ID.
	 *
	 * @return void
	 */
	private function mark_order_cancelled( $order_id ): void {
		$orders = get_option( 'yoco_orders_pending_payment', array() );

		if ( ! is_array( $orders ) || ! isset( $orders[ $order_id ] ) ) {
			return;
		}

		$orders[ $order_id ]['c'] = ( new WC_DateTime( 'now' ) )->__toString();

		// Check again no later than the end of the grace period.
		$grace_period_end = $this->grace_period_end( $orders[ $order_id ] );
		if ( new WC_DateTime( $orders[ $order_id ]['t'] ) > $grace_period_end ) {
			$orders[ $order_id ]['t'] = $grace_period_end->__toString();
		}

		update_option( 'yoco_orders_pending_payment', $orders, false );
	}

	/**
	 * Make every queued order due on the next run.
	 *
	 * @return void
	 */
	private function reschedule_all(): void {
		$orders = get_option( 'yoco_orders_pending_payment', array() );

		if ( ! is_array( $orders ) || empty( $orders ) ) {
			return;
		}

		$now = ( new WC_DateTime( 'now' ) )->__toString();
		foreach ( $orders as $order_id => $order_data ) {
			$orders[ $order_id ]['t'] = $now;
		}
		update_option( 'yoco_orders_pending_payment', $orders, false );
	}

	/**
	 * Log failed payment status request and apply backoff.
	 *
	 * @param  WC_Order $order WC Order.
	 * @param  array    $order_data Order payment data.
	 * @param  array    $response Request response.
	 *
	 * @return void
	 */
	private function handle_failed_status_request( WC_Order $order, array $order_data, array $response ): void {
		$code = (int) $response['code'];

		yoco( Logger::class )->logError(
			sprintf(
				'Payment status request for order #%1$s failed with HTTP %2$d %3$s (attempt %4$d).',
				$order->get_id(),
				$code,
				$response['message'] ?? '',
				(int) $order_data['i']
			)
		);

		if ( in_array( $code, Client::UNAUTHORIZED_STATUS_CODES, true ) ) {
			$this->add_unauthorized_order_note( $order );
		}

		// Other client errors will not recover, add order note and remove order from the list.
		if (
			$code >= 400 && $code < 500
			&& ! in_array( $code, Client::TRANSIENT_STATUS_CODES, true )
			&& ! in_array( $code, Client::UNAUTHORIZED_STATUS_CODES, true )
		) {
			$order->add_order_note(
				sprintf(
					// translators: HTTP status code.
					__( 'Yoco: Payment status check failed permanently (HTTP %d), automatic status updates stopped for this order.', 'yoco-payment-gateway' ),
					$code
				)
			);
			$this->remove_order( $order->get_id() );
			return;
		}

		$this->update_order( $order->get_id() );
	}

	/**
	 * Add unauthorized order note once per order.
	 *
	 * @param  WC_Order $order WC Order.
	 *
	 * @return void
	 */
	private function add_unauthorized_order_note( WC_Order $order ): void {
		$metadata = yoco( Metadata::class );

		if ( $metadata->hasOrderUnauthorizedNote( $order ) ) {
			return;
		}

		$order->add_order_note( __( 'Yoco: Payment status check was rejected by Yoco (unauthorized). Please verify the secret keys in the Yoco Payments settings.', 'yoco-payment-gateway' ) );
		$metadata->markOrderUnauthorizedNote( $order );
	}

	/**
	 * Add missing secret key order note once per order.
	 *
	 * @param  WC_Order $order WC Order.
	 * @param  string   $reason Translated reason from the exception.
	 *
	 * @return void
	 */
	private function add_missing_key_order_note( WC_Order $order, string $reason ): void {
		$metadata = yoco( Metadata::class );

		if ( $metadata->hasOrderMissingKeyNote( $order ) ) {
			return;
		}

		$order->add_order_note(
			sprintf(
				// translators: %s: reason the secret key is missing.
				__( 'Yoco: Payment status check skipped. %s', 'yoco-payment-gateway' ),
				$reason
			)
		);
		$metadata->markOrderMissingKeyNote( $order );
	}

	/**
	 * Add order to the pending payment list.
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
	 * @param  string $status Payment status, empty keeps the stored status.
	 * @param  bool   $increase_counter Counter flag, increase counter by default, prevent increase by passing false.
	 *
	 * @return void
	 */
	private function update_order( $order_id, $status = '', $increase_counter = true ) {
		$orders = get_option( 'yoco_orders_pending_payment', array() );

		if ( isset( $orders[ $order_id ] ) ) {
			$iteration    = (int) $orders[ $order_id ]['i'];
			$status       = $status ? $status : $orders[ $order_id ]['s'];
			$backoff_time = min( self::MAX_BACKOFF_TIME, $iteration * $iteration * $iteration );
			// In case status is failed|canceled set backoff_time to at least 1440 min (1 day).
			if ( 'failed' === $status || 'canceled' === $status ) {
				$backoff_time = max( $backoff_time, 1440 );
			}
			$process_at = new WC_DateTime( 'now + ' . $backoff_time . ' min' );
			// A cancelled order still inside its grace period gets its last check when the period ends, not after.
			$grace_period_end = $this->grace_period_end( $orders[ $order_id ] );
			if ( $grace_period_end && $grace_period_end > new WC_DateTime( 'now' ) && $process_at > $grace_period_end ) {
				$process_at = $grace_period_end;
			}
			// Merge so the cancellation time and any other key on the entry survive the update.
			$orders[ $order_id ] = array_merge(
				$orders[ $order_id ],
				array(
					't' => $process_at->__toString(),
					'i' => $increase_counter ? ++$iteration : $iteration,
					's' => $status,
				)
			);
		}

		update_option( 'yoco_orders_pending_payment', $orders, false );
	}
}
