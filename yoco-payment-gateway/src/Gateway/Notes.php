<?php

namespace Yoco\Gateway;

use WC_Order;
use Yoco\Helpers\Logger;
use Yoco\Repositories\OrdersRepository;

use function Yoco\yoco;

class Notes {

	public function __construct() {
		add_action( 'yoco_payment_gateway/order/meta/yoco_order_checkout_id/updated_successfully', array( $this, 'addSessionIdNoteToOrder' ) );
		add_action( 'yoco_payment_gateway/order/meta/yoco_order_payment_id/updated_successfully', array( $this, 'addPaymentIdNoteToOrder' ) );
		add_action( 'yoco_payment_gateway/order/meta/yoco_order_refund_id/updated_successfully', array( $this, 'addRefundIdNoteToOrder' ) );
	}

	public function addSessionIdNoteToOrder( int $order_id ): void {
		$order = OrdersRepository::getById( $order_id );

		if ( null === $order ) {
			yoco( Logger::class )->logError( sprintf( 'Can\'t add Checkout ID Note, Failed to retrieve Woo Order #%s.', $order_id ) );
			return;
		}

		$session_id = yoco( Metadata::class )->getOrderCheckoutId( $order );

		if ( empty( $session_id ) ) {
			yoco( Logger::class )->logError( sprintf( 'Can\'t add Checkout ID Note, Failed to retrieve Checkout Session ID from Woo Order #%s.', $order_id ) );
			return;
		}

		// translators: Checkout Session ID.
		$this->addNote( $order, sprintf( esc_html__( 'Yoco: Received checkout session ID (%s).', 'yoco_wc_payment_gateway' ), esc_html( $session_id ) ) );
	}

	public function addPaymentIdNoteToOrder( int $order_id ): void {
		$order = OrdersRepository::getById( $order_id );

		if ( null === $order ) {
			yoco( Logger::class )->logError( sprintf( 'Can\'t add Payment ID Note, Failed to retrieve Woo Order #%s.', $order_id ) );
			return;
		}

		$payment_id = yoco( Metadata::class )->getOrderPaymentId( $order );

		if ( empty( $payment_id ) ) {
			yoco( Logger::class )->logError( sprintf( 'Can\'t add Payment ID Note, Failed to retrieve Payment ID from Woo Order #%s.', $payment_id ) );
			return;
		}

		// translators: Payment ID.
		$this->addNote( $order, sprintf( esc_html__( 'Yoco: Received payment session ID (%s).', 'yoco_wc_payment_gateway' ), esc_html( $payment_id ) ) );
	}

	public function addRefundIdNoteToOrder( int $order_id ): void {
		$order = OrdersRepository::getById( $order_id );

		if ( null === $order ) {
			yoco( Logger::class )->logError( sprintf( 'Can\'t add Refund ID Note, Failed to retrieve Woo Order #%s.', $order_id ) );
			return;
		}

		$refund_id = yoco( Metadata::class )->getOrderRefundId( $order );

		if ( empty( $refund_id ) ) {
			yoco( Logger::class )->logError( sprintf( 'Can\'t add Refund ID Note, Failed to retrieve Refund ID from Woo Order #%s.', $order_id ) );
			return;
		}

		// translators: Refund ID.
		$this->addNote( $order, sprintf( esc_html__( 'Yoco: Received refund session ID (%s).', 'yoco_wc_payment_gateway' ), esc_html( $refund_id ) ) );
	}

	public function addNote( WC_Order $order, string $note ): int {
		return $order->add_order_note( $note );
	}
}
