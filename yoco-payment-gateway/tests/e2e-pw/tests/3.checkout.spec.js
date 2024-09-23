const { test, expect } = require( '@playwright/test' );
const config = require( '../playwright.config' );

test.describe( 'Customer can perform checkout', () => {
	test.use( { storageState: process.env.CUSTOMERSTATE } );

	test( 'Can add product to cart', async ( { page } ) => {
		await page.goto( 'shop' );
		await page.click('[data-product_sku="woo-beanie"]' );
		await expect( page.locator( '[data-product_sku="woo-beanie"] + span > .added_to_cart' ) ).toBeVisible();

	} );

	test( 'Can perform checkout', async ( { page } ) => {
		await page.goto( 'checkout' );
		const needAddressFill = await page.$( '.wc-block-components-address-address-wrapper.is-editing' );

		if ( needAddressFill ) {
			await page.fill( '#billing-first_name', 'Jane' );
			await page.fill( '#billing-last_name', 'Smith' );
			await page.fill( '#billing-address_1', 'Street 1' );
			await page.fill( '#billing-city', 'Cape Town' );
			await page.fill( '#billing-postcode', '8000' );
		}

		await page.check( '#radio-control-wc-payment-method-options-class_yoco_wc_payment_gateway' );
		await page.click( '.wc-block-components-checkout-place-order-button' );

		// Wait for navigation to complete
		await page.waitForURL('https://c.yoco.com/checkout/**');

		// Fill input fields in the iframes
		const cardNumberFrame = await page.frameLocator('iframe[title="Secure card number input frame"]').first();
		const expDateFrame = await page.frameLocator('iframe[title="Secure card expiration date input frame"]').first();
		const cvvFrame = await page.frameLocator('iframe[title="Secure card security code input frame"]').first();

		const cardNumberInput = await cardNumberFrame.locator('.card-number-input');
		const expDateInput = await expDateFrame.locator('input[aria-label="Card expiration date"]');
		const cvvInput = await cvvFrame.locator('.card-security-code');

		await cardNumberInput.fill('4111 1111 1111 1111');
		await expDateInput.fill('10/28');
		await cvvInput.fill('123');

		// Submit the payment.
		await page.click( 'button[type="submit"]' );

		// Wait for order recived page to load.
		await page.waitForURL( config.use.baseURL + '/**/order-received/**' );

		await expect(page.locator( '.wc-block-order-confirmation-status p' ) ).toHaveText(
			'Thank you. Your order has been received.'
		);
	} );
} );
