<?php

namespace Yoco\Integrations\Yoco\Requests;

use WC_Order;
use WC_Order_Item;
use Yoco\Helpers\MoneyFormatter as Money;
use Yoco\Gateway\Models\LineItem;
use Yoco\Gateway\Models\LineItemPricingDetails;
use Yoco\Gateway\Models\Metadata;
use Yoco\Gateway\Models\Payload;

use function Yoco\yoco;

class Checkout {

	private ?WC_Order $order = null;

	public function __construct( WC_Order $order ) {
		$this->order = $order;
	}

	public function buildPayload(): Payload {
		return ( new Payload() )
			->setAmount( $this->getOrderTotal() )
			->setCurrency( $this->order->get_currency() )
			->setSuccessUrl( $this->absoluteUrl( $this->order->get_checkout_order_received_url() ) )
			->setCancelUrl( $this->absoluteUrl( $this->order->get_checkout_payment_url() ) )
			->setFailureUrl( $this->absoluteUrl( $this->getOrderCheckoutPaymentUrl( 'failed' ) ) )
			->setMetadata( $this->buildMetadata( $this->order ) )
			->setLineItems( $this->buildLineItems( $this->order ) )
			->setExternalId( $this->order->get_order_key() );
	}

	private function buildMetadata( WC_Order $order ): Metadata {
		$note = join(
			' ',
			array(
				__( 'order', 'yoco_wc_payment_gateway' ),
				$order->get_id(),
				__( 'from', 'yoco_wc_payment_gateway' ),
				$order->get_billing_first_name(),
				$order->get_billing_last_name(),
				'(' . $order->get_billing_email() . ')',
			)
		);

		return ( new Metadata() )
			->setBillNote( $note )
			->setCustomerLastName( $order->get_billing_last_name() )
			->setCustomerFirstName( $order->get_billing_first_name() )
			->setCustomerEmailAddress( $order->get_billing_email() );
	}

	private function buildLineItems( WC_Order $order ): array {
		return array_map(
			function ( WC_Order_Item $item ) {
				return $this->buildLineItem( $item );
			},
			array_values( $order->get_items() )
		);
	}

	private function buildLineItem( WC_Order_Item $item ): array {
		return ( new LineItem() )
			->setDisplayName( $item->get_name() )
			->setQuantity( $item->get_quantity() )
			->setPricingDetails( $this->buildLineItemPricingDetails( $item ) )
			->toArray();
	}

	private function buildLineItemPricingDetails( WC_Order_Item $item ): LineItemPricingDetails {
		return ( new LineItemPricingDetails() )
			->setPrice( yoco( Money::class )->format( $this->order->get_line_total( $item ) / $item->get_quantity() ) )
			->setTaxAmount( yoco( Money::class )->format( $this->order->get_line_tax( $item ) ) )
			->setDiscountAmount(
				yoco( Money::class )->format( $this->order->get_line_subtotal( $item ) )
				- yoco( Money::class )->format( $this->order->get_line_total( $item ) )
			);
	}

	private function getOrderTotal(): int {
		return yoco( Money::class )->format( $this->order->get_total() );
	}

	private function getOrderSubtotal(): int {
		$subtotal = yoco( Money::class )->format( $this->order->get_subtotal() );

		return $subtotal + $this->getOrderTotalTax() + $this->getOrderTotalShipping();
	}

	private function getOrderTotalTax(): int {
		return yoco( Money::class )->format( $this->order->get_total_tax() );
	}

	private function getOrderTotalDiscount(): int {
		return yoco( Money::class )->format( $this->order->get_total_discount() );
	}

	private function getOrderTotalShipping(): int {
		return yoco( Money::class )->format( $this->order->get_shipping_total() );
	}

	private function getOrderCheckoutPaymentUrl( string $status ): string {
		return add_query_arg(
			array(
				'yoco_checkout_status' => $status,
			),
			$this->order->get_checkout_order_received_url()
		);
	}

	private function absoluteUrl( string $url ): string {
		if ( ! preg_match( '/^https?:\/\//', $url ) ) {
			$url = home_url( $url );
		}

		return $url;
	}
}
