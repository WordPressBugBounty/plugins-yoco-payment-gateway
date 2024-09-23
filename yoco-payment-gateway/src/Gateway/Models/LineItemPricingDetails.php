<?php

namespace Yoco\Gateway\Models;

class LineItemPricingDetails {

	private ?int $price = null;

	private ?int $taxAmount = null;

	private ?int $discountAmount = null;

	public function setPrice( int $price ): self {
		$this->price = $price;

		return $this;
	}

	public function setTaxAmount( int $taxAmount ): self {
		$this->taxAmount = $taxAmount;

		return $this;
	}

	public function setDiscountAmount( int $discountAmount ): self {
		$this->discountAmount = $discountAmount;

		return $this;
	}

	public function toArray(): array {
		return array(
			'price'          => $this->price,
			'taxAmount'      => $this->taxAmount,
			'discountAmount' => $this->discountAmount,
		);
	}
}
