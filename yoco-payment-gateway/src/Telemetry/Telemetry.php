<?php

namespace Yoco\Telemetry;

use Yoco\Telemetry\Models\TelemetryObject;

class Telemetry {

	public function getObject(): TelemetryObject {
		return new TelemetryObject();
	}

	public function getData(): array {
		$object = $this->getObject();

		return apply_filters(
			'yoco_payment_gateway/telemetry/data',
			array(
				'domain'                   => $object->getHostUrl(),
				'webhooks'                 => $object->getWebhooks(),
				'preferredWebhook'         => $object->getPreferredWebhook(),
				'installationName'         => $object->getSiteName(),
				'phpVersion'               => $object->getPhpVersion(),
				'wordPressVersion'         => $object->getWpVersion(),
				'wooCommerceVersion'       => $object->getWcVersion(),
				'yocoPaymentPluginVersion' => $object->getYocoPluginVersion(),
				'yocoPaymentPluginMode'    => $object->getYocoPluginMode(),
				'activeTheme'              => $object->getActiveThemeDetails(),
				'installedThemes'          => $object->getInstalledThemesDetails(),
				'installedPlugins'         => $object->getInstalledPluginsDetails(),
			)
		);
	}
}
