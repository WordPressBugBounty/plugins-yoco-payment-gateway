<?php

require 'helpers.php';

spl_autoload_register(
	function ( $className ) {
		$className = ltrim( $className, '\\' );
		$fileName  = '';
		$namespace = '';
		if ( $lastNsPos = strrpos( $className, '\\' ) ) {
			$namespace = substr( $className, 0, $lastNsPos );
			$className = substr( $className, $lastNsPos + 1 );
			$fileName  = str_replace( '\\', DIRECTORY_SEPARATOR, preg_replace( '/^Yoco/', 'src', $namespace ) . DIRECTORY_SEPARATOR );
		}

		if ( false === strpos( $namespace, 'Yoco' ) ) {
			return;
		}

		$fileName .= str_replace( '_', DIRECTORY_SEPARATOR, $className ) . '.php';
		$filePath  = trailingslashit( YOCO_PLUGIN_PATH ) . $fileName;

		if ( file_exists( $filePath ) ) {
			include_once $filePath;
		}
	}
);
