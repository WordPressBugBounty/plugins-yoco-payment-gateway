<?php

namespace Yoco\Integrations\Yoco\Webhooks\Models;

class WebhookPayload {

	private ?string $checkoutId = null;

	private ?string $paymentId = null;

	private ?string $id = null;

	private ?string $currency = null;

	private ?string $eventType = null;

	private ?string $status = null;

	private ?int $amount = null;

	private ?int $refundableAmount = null;

	private ?string $failureReason = null;

	public function setId( string $id ): void {
		$this->id = $id;
	}

	public function hasId(): bool {
		return ! empty( $this->getId() );
	}

	public function getId(): ?string {
		return $this->id;
	}

	public function setCheckoutId( string $checkoutId ): void {
		$this->checkoutId = $checkoutId;
	}

	public function hasCheckoutId(): bool {
		return ! empty( $this->getCheckoutId() );
	}

	public function getCheckoutId(): ?string {
		return $this->checkoutId;
	}

	public function setPaymentId( string $paymentId ): void {
		$this->paymentId = $paymentId;
	}

	public function hasPaymentId(): bool {
		return ! empty( $this->getPaymentId() );
	}

	public function getPaymentId(): ?string {
		return $this->paymentId;
	}

	public function setCurrency( string $currency ): void {
		$this->currency = $currency;
	}

	public function hasCurrency(): bool {
		return ! empty( $this->getCurrency() );
	}

	public function getCurrency(): ?string {
		return $this->currency;
	}

	public function setEventType( string $eventType ): void {
		$this->eventType = $eventType;
	}

	public function hasEventType(): bool {
		return ! empty( $this->eventType );
	}

	public function getEventType(): ?string {
		return $this->eventType;
	}

	public function setStatus( string $status ): void {
		$this->status = $status;
	}

	public function hasStatus(): bool {
		return ! empty( $this->status );
	}

	public function getStatus(): ?string {
		return $this->status;
	}

	public function setAmount( int $amount ): void {
		$this->amount = $amount;
	}

	public function hasAmount(): bool {
		return ! empty( $this->amount );
	}

	public function getAmount(): ?int {
		return $this->amount;
	}

	public function setRefundableAmount( int $amount ): void {
		$this->refundableAmount = $amount;
	}

	public function hasRefundableAmount(): bool {
		return ! empty( $this->refundableAmount );
	}

	public function getRefundableAmount(): ?int {
		return $this->refundableAmount;
	}

	public function setFailureReason( string $failureReason ): void {
		$this->failureReason = $failureReason;
	}

	public function hasFailureReason(): bool {
		return ! empty( $this->failureReason );
	}

	public function getFailureReason(): ?string {
		return $this->failureReason;
	}
}
