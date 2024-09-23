<?php

namespace Yoco\Gateway\Models;

class Metadata {

	private string $billNote = '';

	private string $customerLastName = '';

	private string $customerFirstName = '';

	private string $customerEmailAddress = '';

	public function setBillNote( string $billNote ): self {
		$this->billNote = $billNote;

		return $this;
	}

	public function setCustomerLastName( string $customerLastName ): self {
		$this->customerLastName = $customerLastName;

		return $this;
	}

	public function setCustomerFirstName( string $customerFirstName ): self {
		$this->customerFirstName = $customerFirstName;

		return $this;
	}

	public function setCustomerEmailAddress( string $customerEmailAddress ): self {
		$this->customerEmailAddress = $customerEmailAddress;

		return $this;
	}

	public function toArray(): array {
		return array(
			'billNote'             => $this->billNote,
			'customerLastName'     => $this->customerLastName,
			'customerFirstName'    => $this->customerFirstName,
			'customerEmailAddress' => $this->customerEmailAddress,
		);
	}
}
