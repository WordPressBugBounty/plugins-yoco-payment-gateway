const { test, expect } = require( '@playwright/test' );
const { exec } = require( 'child_process' );

test.describe(
	'A basic set of tests to ensure enviroment is loading',
	() => {
		test( 'Load the home page', async ( { page } ) => {
			await page.goto( '/' );
			const title = page.locator( 'header .wp-block-site-title' );
			await expect( title ).toHaveText(
				'Yoco e2e tests'
			);
		} );

		test.describe( 'Sign in as admin', () => {
			test.use( {
				storageState: process.env.ADMINSTATE,
			} );
			test( 'Load wp-admin', async ( { page } ) => {
				await page.goto( '/wp-admin' );
				const title = page.locator( 'div.wrap > h1' );
				await expect( title ).toHaveText( 'Dashboard' );
			} );
		} );

		test.describe( 'Sign in as customer', () => {
			test.use( {
				storageState: process.env.CUSTOMERSTATE,
			} );
			test( 'Load customer my account page', async ( { page } ) => {
				await page.goto( '/my-account' );
				const title = page.locator( 'h1.wp-block-post-title' );
				await expect( title ).toHaveText( 'My account' );
			} );
		} );


		test.describe(
			'Ensure enviroment configuration is complete',
			() => {
				test.use( { storageState: process.env.ADMINSTATE } );

				test( 'Can make sure WooCommerce is activated.', async ( { page } ) => {
					await page.goto( '/wp-admin/plugins.php' );
					// Expect the woo plugin to be displayed -- if there's an update available, it has the same data-slug attribute
					await expect(
						page.locator( "//tr[@data-slug='woocommerce'][1]" )
					).toBeVisible();
					// Expect it to have an active class
					await expect(
						page.locator( "//tr[@data-slug='woocommerce'][1]" )
					).toHaveClass( /active/ );
				} );

				test( 'Can configure permalink settings', async ( { page } ) => {
					await page.goto( 'wp-admin/options-permalink.php' );
					// Select "Post name" option in common settings section
					await page.check( 'label >> text=Post name' );
					// Select "Custom base" in product permalinks section
					await page.check( 'label >> text=Custom base' );
					// Fill custom base slug to use
					await page.fill( '#woocommerce_permalink_structure', '/product/' );
					await page.click( '#submit' );
					// Verify that settings have been saved
					await expect(
						page.locator( '#setting-error-settings_updated' )
					).toContainText( 'Permalink structure updated.' );
					await expect( page.locator( '#permalink_structure' ) ).toHaveValue(
						'/%postname%/'
					);
					await expect(
						page.locator( '#woocommerce_permalink_structure' )
					).toHaveValue( '/product/' );
				} );
			}
		);
	}
);
