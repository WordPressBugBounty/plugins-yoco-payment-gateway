<?php

namespace Yoco;

if ( ! function_exists( 'Yoco\\yoco_load' ) ) {
	/**
	 * @return array
	 */
	function yoco_load() {
		/**
		 * @var Init $system
		*/
		$system = Init::get();
		return $system->getClasses();
	}
}

if ( ! function_exists( 'Yoco\\yoco' ) ) {
	/**
	 * @return object
	 */
	function yoco( string $className = '' ) {
		/**
		 * @var Init $system
		*/
		$system = Init::get();
		return $system->getClass( $className );
	}
}

if ( ! function_exists( 'Yoco\\asset_path' ) ) {
	function asset_path( string $asset ): string {
		$config = trailingslashit( YOCO_ASSETS_PATH );
		$asset  = ltrim( $asset, '/' );
		return $config . $asset;
	}
}
