const { test, expect } = require( '@playwright/test' );
const config = require( '../playwright.config' );

test.describe( 'Store owner can finish setup', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'Can deactivate Yoco plugin', async ( { page } ) => {
		await page.goto( 'wp-admin/plugins.php' );
		// Check if the element exists before clicking
		const deactivateButton = await page.$('#deactivate-yoco-payment-gateway');
		if ( deactivateButton ) {
			await page.click( '#deactivate-yoco-payment-gateway' );
		}

		// Verify changes have been saved
		await expect( page.locator( '#activate-yoco-payment-gateway' ) ).toBeVisible();
	} );

	test( 'Can activate Yoco plugin', async ( { page } ) => {
		await page.goto( 'wp-admin/plugins.php' );
		// Check if the element exists before clicking
		const isDeactivated = await page.$( '#activate-yoco-payment-gateway' );
		if ( isDeactivated ) {
			await page.click('#activate-yoco-payment-gateway');
			// Verify changes have been saved
			await expect( page.locator( 'a[href*="section=class_yoco_wc_payment_gateway"]' ) ).toHaveText(' Settings');
		}

		// Expect yoco plugin to be installed and active.
		await expect( page.locator( "//tr[@data-slug='yoco-payment-gateway'][1]" ) ).toBeVisible();
		await expect( page.locator( "//tr[@data-slug='yoco-payment-gateway'][1]" ) ).toHaveClass( /active/ );
	} );

	test( 'Can navigate to settings', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=checkout&section=class_yoco_wc_payment_gateway' );
		// Verify changes have been saved
		await expect( page.locator( '#mainform > h2' ) ).toHaveText(/Yoco Payments/);
	} );

	test( 'Can perform installation process', async ( { page } ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-settings&tab=checkout&section=class_yoco_wc_payment_gateway' );
		// Verify changes have been saved
		await page.check('#woocommerce_class_yoco_wc_payment_gateway_enabled' );
		await expect( page.locator( '#woocommerce_class_yoco_wc_payment_gateway_enabled' ) ).toBeChecked();
		await page.selectOption( '#woocommerce_class_yoco_wc_payment_gateway_mode', 'test' );
		await page.fill( '#woocommerce_class_yoco_wc_payment_gateway_live_secret_key', config.use.liveSecretKey );
		await page.fill( '#woocommerce_class_yoco_wc_payment_gateway_test_secret_key', config.use.testSecretKey );
		await page.check( '#woocommerce_class_yoco_wc_payment_gateway_debug' );
		await expect( page.locator( '#woocommerce_class_yoco_wc_payment_gateway_debug' ) ).toBeChecked();
		await page.click( '.woocommerce-save-button' );

		await expect( page.locator( '#mainform .yoco-notice > p' ) ).toHaveText('Yoco Payments: Plugin installed successfully.' );
	} );


} );
