<?php

namespace Yoco;

use Yoco\Core\Actions;
use Yoco\Core\Constants;
use Yoco\Core\Setup;
use Yoco\Core\Singleton;
use Yoco\Core\Dependencies;
use Yoco\Core\Environment;
use Yoco\Core\Language;
use Yoco\Core\Plugin;
use Yoco\Cron\CronScheduler;
use Yoco\Gateway\Admin;
use Yoco\Gateway\Admin\Notices;
use Yoco\Gateway\Checkout\Availability;
use Yoco\Gateway\Checkout\Method;
use Yoco\Gateway\Notes;
use Yoco\Gateway\Metadata;
use Yoco\Gateway\PaymentStatusScheduler;
use Yoco\Gateway\Order;
use Yoco\Gateway\Provider;
use Yoco\Gateway\Settings;
use Yoco\Helpers\Admin\Notices as AdminNotices;
use Yoco\Helpers\Logger;
use Yoco\Helpers\Money\Currencies;
use Yoco\Helpers\MoneyFormatter as Money;
use Yoco\Helpers\Security\SSL;
use Yoco\Helpers\Storage\Options;
use Yoco\Helpers\Versioner;
use Yoco\Installation\Installation;
use Yoco\Installations\InstallationsManager;
use Yoco\Integrations\Webhook\Guard;
use Yoco\Integrations\Yoco\Webhooks\Events\WebhookEventsManager;
use Yoco\Integrations\Yoco\Webhooks\REST\Rewrites;
use Yoco\Integrations\Yoco\Webhooks\REST\Router;
use Yoco\Telemetry\Jobs\TelemetryUpdateJob;
use Yoco\Telemetry\Telemetry;

final class Init extends Singleton {

	public array $public = array();

	public array $private = array();

	public function __construct() {

		// Env.
		$this->bindPublic( Environment::class );
		$this->bindPublic( Versioner::class );
		$this->bindPublic( Constants::class );

		// Debug tools.
		$this->bindPublic( Logger::class );

		// Help utils.
		$this->bindPublic( Money::class );
		$this->bindPublic( Notes::class );
		$this->bindPublic( AdminNotices::class );
		$this->bindPublic( Metadata::class );
		$this->bindPublic( SSL::class );
		$this->bindPublic( Options::class );
		$this->bindPublic( Currencies::class );

		// CRON & CRON jobs.
		// $this->bindPublic(CronScheduler::class);

		// Installation.
		$this->bindPublic( InstallationsManager::class );

		// Webhook Validator.
		$this->bindPublic( Guard::class );

		// Installation.
		$this->bindPublic( Telemetry::class );

		// Webhook REST.
		$this->bindPrivate( Router::class );
		$this->bindPrivate( Rewrites::class );

		// Webhook utils.
		$this->bindPublic( WebhookEventsManager::class );

		// Gateway.
		$this->bindPublic( Provider::class );
		$this->bindPrivate( Settings::class );
		$this->bindPrivate( Availability::class );
		$this->bindPrivate( Method::class );
		$this->bindPrivate( Notices::class );

		// Installation.
		$this->bindPublic( Installation::class );

		// Core.
		$this->bindPrivate( Plugin::class );
		$this->bindPublic( Setup::class );
		$this->bindPrivate( Dependencies::class );
		// $this->bindPrivate(Language::class);
		$this->bindPrivate( Actions::class );

		$this->bindPublic( PaymentStatusScheduler::class );
	}

	private function bindPublic( string $class, array $args = array() ): void {
		$this->public[ $class ] = new $class( ... $args );
	}

	private function bindPrivate( string $class, array $args = array() ): void {
		$this->private[ $class ] = new $class( ... $args );
	}

	private function hasClass( string $className ): void {
		if ( ! array_key_exists( $className, $this->public ) ) {
			throw new \Exception( sprintf( __( 'Class %1$s hasn\'t been binded!', 'yoco_wc_payment_gateway' ), $className ) );
		}
	}

	/**
	 * @return array
	 */
	public function getClasses() {
		return $this->public;
	}

	/**
	 * @return object
	 */
	public function getClass( string $className ) {
		$this->hasClass( $className );
		return $this->public[ $className ];
	}
}
