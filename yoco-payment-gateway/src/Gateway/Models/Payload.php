<?php

namespace Yoco\Gateway\Models;

class Payload {

	private ?int $amount = null;

	private string $currency = '';

	private string $successUrl = '';

	private string $cancelUrl = '';

	private string $failureUrl = '';

	private ?Metadata $metadata = null;

	private ?int $totalDiscount = null;

	private ?int $totalTaxAmount = null;

	private ?int $subtotalAmount = null;

	private array $lineItems = array();

	private string $externalId = '';

	private string $productType = 'woocommerce';

	public function setAmount( int $amount ): self {
		$this->amount = $amount;

		return $this;
	}

	public function setCurrency( string $currency ): self {
		$this->currency = $currency;

		return $this;
	}

	public function setSuccessUrl( string $url ): self {
		$this->successUrl = $url;

		return $this;
	}

	public function setCancelUrl( string $url ): self {
		$this->cancelUrl = $url;

		return $this;
	}

	public function setFailureUrl( string $url ): self {
		$this->failureUrl = $url;

		return $this;
	}

	public function setMetadata( Metadata $metadata ): self {
		$this->metadata = $metadata;

		return $this;
	}

	public function setTotalDiscount( int $amount ): self {
		$this->totalDiscount = $amount;

		return $this;
	}

	public function setTotalTaxAmount( int $amount ): self {
		$this->totalTaxAmount = $amount;

		return $this;
	}

	public function setSubtotalAmount( int $amount ): self {
		$this->subtotalAmount = $amount;

		return $this;
	}

	public function setLineItems( array $items ): self {
		$this->lineItems = $items;

		return $this;
	}

	public function setExternalId( string $externalId ): self {
		$this->externalId = $externalId;

		return $this;
	}

	public function toArray(): array {
		return array(
			'amount'         => $this->amount,
			'currency'       => $this->currency,
			'successUrl'     => $this->successUrl,
			'cancelUrl'      => $this->cancelUrl,
			'failureUrl'     => $this->failureUrl,
			'metadata'       => $this->metadata->toArray(),
			'totalDiscount'  => $this->totalDiscount,
			'totalTaxAmount' => $this->totalTaxAmount,
			'subtotalAmount' => $this->subtotalAmount,
			'lineItems'      => $this->lineItems,
			'externalId'     => $this->externalId,
			'productType'    => $this->productType,
		);
	}
}
