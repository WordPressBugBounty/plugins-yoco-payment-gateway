<?php

namespace Yoco\Helpers\Validation;

use Yoco\Helpers\Validation\ValidatorErrorBag;

abstract class Validator implements ValidatorInterface {

	protected array $rules = array();

	private ?ValidatorErrorBag $errorBag = null;

	public function __construct() {
		$this->errorBag = new ValidatorErrorBag();
	}

	public function validate( array $data, ?array $rules = null, string $parent = '' ): void {
		if ( null === $rules ) {
			$rules = $this->rules;
		}

		foreach ( $rules as $key => $rule ) {
			if ( ! array_key_exists( $key, $data ) ) {
				$this->errorBag->pushError( "'{$parent}{$key}' is required" );
				continue;
			}

			if ( is_array( $rule ) ) {
				if ( $this->validateType( $data[ $key ], 'array', "{$parent}{$key}" ) ) {
					$this->validate( $data[ $key ], $rule, "{$parent}{$key}." );
				}
			} else {
				$this->validateType( $data[ $key ], $rule, "{$parent}{$key}" );
			}
		}
	}

	/**
	 * @param mixed $value
	 */
	protected function validateType( $value, string $type, string $key ): bool {
		$isValid = false;

		switch ( $type ) {
			case 'string':
				$isValid = is_string( $value );
				break;

			case 'array':
				$isValid = is_array( $value );
				break;

			default:
				$isValid = false;
				break;
		}

		if ( false === $isValid ) {
			$this->errorBag->pushError( "'{$key}' must be of type {$type}" );
		}

		return $isValid;
	}

	public function getErrorBag(): ?ValidatorErrorBag {
		return $this->errorBag;
	}
}
