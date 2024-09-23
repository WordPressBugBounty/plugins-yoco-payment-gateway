<?php

namespace Yoco\Gateway\Refund;

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

	public function getCheckoutId(): string {
		return yoco( Metadata::class )->getOrderCheckoutId( $this->order );
	}

	private function getUrl(): string {
		$url = $this->installation->getCheckoutApiUrl();

		return trailingslashit( $url ) . $this->getCheckoutId() . '/refund';
	}

	private function getArgs(): array {
		return array(
			'headers' => $this->getHeaders(),
		);
	}

	private function getHeaders() {
		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => $this->installation->getApiBearer(),
			'X-Product'     => 'woocommerce',
		);

		return apply_filters( 'yoco_payment_gateway/refund/request/headers', $headers );
	}
}
