<?php

namespace Yoco\Core;

use Yoco\Helpers\Versioner;

use function Yoco\yoco;

class Dependencies {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'public' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin' ) );
	}

	public function register(): void {
		$version = yoco( Versioner::class )->getDependenciesVersion();

		wp_register_style( 'yoco/admin', YOCO_ASSETS_URI . '/styles/admin.css', array(), $version );
		wp_register_script( 'yoco/admin', YOCO_ASSETS_URI . '/scripts/admin.js', array(), $version, true );
	}

	public function public(): void {
		wp_enqueue_style( 'yoco/public' );
		wp_enqueue_script( 'yoco/public' );
	}

	public function admin(): void {
		wp_enqueue_style( 'yoco/admin' );
		wp_enqueue_script( 'yoco/admin' );
	}
}
