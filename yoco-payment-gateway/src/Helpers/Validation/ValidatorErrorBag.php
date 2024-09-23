<?php

namespace Yoco\Helpers\Validation;

class ValidatorErrorBag {

	private array $errors = array();

	public function pushError( string $error ): void {
		$this->errors[] = $error;
	}

	public function getErrors(): array {
		return $this->errors;
	}

	public function hasErrors(): bool {
		return ! empty( $this->getErrors() );
	}

	public function clearErrors(): void {
		$this->errors = array();
	}
}
