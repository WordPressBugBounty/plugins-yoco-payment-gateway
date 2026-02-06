<?php

namespace Yoco\Helpers\Validation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ValidatorInterface {

	public function validate( array $data, ?array $rules = null, string $parent = ''): void;

	public function getErrorBag(): ?ValidatorErrorBag;
}
