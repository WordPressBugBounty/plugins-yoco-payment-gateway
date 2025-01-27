<?php

namespace Yoco\Gateway\Refunds;

use WC_Order;
use Yoco\Gateway\Metadata;
use Yoco\Helpers\Http\Client;
use Yoco\Installation\Installation;

use function Yoco\yoco;

class Request {

	private ?WC_Order $order = null;

	private ?Installation $installation = null;

	public function __construct( WC_Order $order ) {
		$this->order        = $order;
		$this->installation = yoco( Installation::class );
	}

	public function get(): array {
		try {
			$client = new Client();

			$url  = $this->getUrl();
			$args = $this->getArgs();

			return $client->get( $url, $args );
		} catch ( \Throwable $th ) {
			throw $th;
		}
	}

	public function getPaymentId(): string {
		return yoco( Metadata::class )->getOrderPaymentId( $this->order );
	}

	private function getUrl(): string {
		$url = $this->installation->getPaymentApiUrl();

		return trailingslashit( $url ) . $this->getPaymentId() . '/refunds';
	}

	private function getArgs(): array {

		$args = array(
			'headers' => $this->getHeaders(),
		);

		return $args;
	}

	private function getHeaders() {
		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => $this->installation->getApiBearer( $this->order->get_meta( 'yoco_order_payment_mode', true ) ),
			'X-Product'     => 'woocommerce',
		);

		return apply_filters( 'yoco_payment_gateway/refunds/request/headers', $headers );
	}
}
