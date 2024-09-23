<?php

namespace Yoco\Helpers\Validation;

interface ValidatorInterface {

	public function validate( array $data, ?array $rules = null, string $parent = ''): void;

	public function getErrorBag(): ?ValidatorErrorBag;
}
