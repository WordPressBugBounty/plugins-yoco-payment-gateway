<?php

namespace Yoco\Gateway\Payment;

use WC_Order;
use Yoco\Gateway\Metadata;
use Yoco\Helpers\Http\Client;
use Yoco\Installation\Installation;
use Yoco\Integrations\Yoco\Requests\Checkout;

use function Yoco\yoco;

class Request {

	private ?WC_Order $order = null;

	private ?Installation $installation = null;

	public function __construct( WC_Order $order ) {
		$this->order        = $order;
		$this->installation = yoco( Installation::class );
	}

	public function send(): array {
		try {
			$client = new Client();

			$url  = $this->getUrl();
			$args = $this->getArgs();

			return $client->post( $url, $args );
		} catch ( \Throwable $th ) {
			throw $th;
		}
	}

	public function get(): array {
		try {
			$checkout_id = yoco( Metadata::class )->getOrderCheckoutId( $this->order );
			if ( empty( $checkout_id ) ) {
				throw new \Exception( 'Yoco Checkout ID not found.' );
			}

			$client = new Client();
			$url    = $this->getUrl() . '/' . $checkout_id;
			$args   = array( 'headers' => $this->getHeadersForMode() );

			return $client->get( $url, $args );
		} catch ( \Throwable $th ) {
			throw $th;
		}
	}

	private function getUrl(): string {
		return $this->installation->getCheckoutApiUrl();
	}

	private function getArgs(): array {
		return array(
			'headers' => $this->getHeaders(),
			'body'    => $this->getBody(),
		);
	}

	public function getHeaders() {
		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => $this->installation->getApiBearer(),
			'X-Product'     => 'woocommerce',
		);

		return apply_filters( 'yoco_payment_gateway/payment/request/headers', $headers );
	}

	public function getHeadersForMode() {

		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => $this->installation->getApiBearer( $this->order->get_meta( 'yoco_order_payment_mode', true ) ),
			'X-Product'     => 'woocommerce',
		);

		return apply_filters( 'yoco_payment_gateway/payment/request/headers', $headers );
	}

	private function getBody() {
		$checkout = new Checkout( $this->order );

		$body = $checkout->buildPayload()->toArray();
		$body = apply_filters( 'yoco_payment_gateway/payment/request/body', $body );

		return json_encode( $body, JSON_UNESCAPED_SLASHES );
	}
}
