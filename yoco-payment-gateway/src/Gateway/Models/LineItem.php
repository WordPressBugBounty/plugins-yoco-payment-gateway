<?php

namespace Yoco\Gateway\Models;

class LineItem {

	private string $displayName = '';

	private int $quantity = 0;

	private ?LineItemPricingDetails $pricingDetails = null;

	public function setDisplayName( string $name ): self {
		$this->displayName = $name;

		return $this;
	}

	public function setQuantity( int $quantity ): self {
		$this->quantity = $quantity;

		return $this;
	}

	public function setPricingDetails( LineItemPricingDetails $pricingDetails ): self {
		$this->pricingDetails = $pricingDetails;

		return $this;
	}

	public function toArray(): array {
		return array(
			'displayName'    => $this->displayName,
			'quantity'       => $this->quantity,
			'pricingDetails' => $this->pricingDetails->toArray(),
		);
	}
}
