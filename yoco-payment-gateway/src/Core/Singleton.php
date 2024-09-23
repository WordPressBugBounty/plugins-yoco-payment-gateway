<?php

namespace Yoco\Core;

abstract class Singleton {

	/**
	 * @var Singleton[]
	 */
	protected static array $instances;

	public static function get() {
		$class = get_called_class();
		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new static();
		}
		return static::$instances[ $class ];
	}

	/**
	 * is not allowed to call from outside to prevent from creating multiple instances,
	 * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
	 */
	abstract public function __construct();

	/**
	 * prevent the instance from being cloned (which would create a second instance of it)
	 */
	private function __clone() {
	}

	/**
	 * prevent from being unserialized (which would create a second instance of it)
	 */
	public function __wakeup() {
		throw new \Exception( __( 'Cannot unserialize singleton', 'yoco_wc_payment_gateway' ) );
	}
}
