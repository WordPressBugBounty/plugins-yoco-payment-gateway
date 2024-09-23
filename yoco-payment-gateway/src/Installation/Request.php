<?php

namespace Yoco\Installation;

use Yoco\Helpers\Http\Client;
use Yoco\Telemetry\Telemetry;

use function Yoco\yoco;

class Request {

	private ?Installation $installation = null;

	private ?Telemetry $telemetry = null;

	public function __construct() {
		$this->installation = yoco( Installation::class );
		$this->telemetry    = yoco( Telemetry::class );
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

	private function getUrl(): string {
		return $this->installation->getApiUrl();
	}

	private function getArgs(): array {
		return array(
			'headers' => $this->getHeaders(),
			'body'    => $this->getBody(),
			'timeout' => 10,
		);
	}

	private function getHeaders() {
		$headers = array(
			'Content-Type'    => 'application/json',
			'Authorization'   => $this->installation->getApiBearer(),
			'Idempotency-Key' => hash( 'SHA256', $this->getBody() ),
		);

		return apply_filters( 'yoco_payment_gateway/installation/request/headers', $headers );
	}

	private function getBody() {
		$body = $this->telemetry->getData();
		$body = apply_filters( 'yoco_payment_gateway/installation/request/body', $body );

		return json_encode( $body, JSON_UNESCAPED_SLASHES );
	}
}
