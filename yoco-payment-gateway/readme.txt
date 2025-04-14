=== Yoco Payments ===
Contributors: Yoco
Tags: woocommerce,payment gateway
Requires at least: 5.0.0
Tested up to: 6.8
Requires PHP: 7.4.0
Stable tag: 3.8.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The Yoco Payments plugin lets you easily accept payments via Yoco Payments on your WooCommerce WordPress site.

== Description ==

### Accept payments with Yoco

Whether you're a small or medium-sized e-commerce business, the Yoco Payments for WooCommerce plugin is the perfect solution. We've designed our payment process to be both user-friendly and secure for both you and your customers.

Our pricing model is transparent and straightforward: we charge only per transaction, with no hidden fees, no monthly fees, and no plugin fees. Additionally, you'll have access to real-time data and insights via your Yoco dashboard. This makes it super easy to manage a healthy cash flow.

### Why use the Yoco Payments for WooCommerce plugin?

* No hidden or monthly fees
* Get paid the same day with [Yoco Instant Payouts](https://www.yoco.com/za/instant-payout/) (if eligible)
* PCI DSS compliant
* Supports 3D Secure
* Your customers enjoy a seamless and safe payment experience
* Refund orders directly from your WooCommerce dashboard

### Transparent pricing

Only pay when you get paid!

* Local Cards: 2.95% - 2.6% ex. VAT
* International Cards: 3.5% - 3.05% ex. VAT
* American Express: 3.5% - 3.05% ex. VAT
* Instant EFT: 2% ex. VAT

For further pricing details, please view [pricing information](https://www.yoco.com/za/fees/).

### Quick installation and setup

https://www.youtube.com/watch?v=Ztfg8SOuxDI

**Step 1:** [Sign up with Yoco](https://hello.yoco.com/signup/?services=website&skipServiceSelectionStep=true&skipProductCatalogueStep=true&_gl=1*z76dm7*_ga*MjA4ODU2Nzc5Ni4xNjI3OTAyOTYw*_ga_7QHHCFW0TM*MTY0MTU0MzE3Mi42Ni4xLjE2NDE1NDM3MjQuNTk.&_ga=2.242169797.673485731.1641387328-2088567796.1627902960).

**Step 2:** Install Yoco for WooCommerce on your WordPress website.

**Step 3:** Activate Yoco on your WooCommerce WebShop.

**Step 4:** Do a test transaction to make sure you’re all set.

That’s it! You’re ready to accept payments.

For more details, please view [Yoco Gateway for WooCommerce: User Guide](https://support.yoco.help/s/article/Yoco-for-WooCommerce).

### All your sales orders in one place

The transaction info is captured in the Orders menu, and you can view all payments in your WordPress admin dashboard to stay on top of everything.

In addition, you can see all your online and in-store payments in one place in your [Yoco Business Portal](https://portal.yoco.co.za/onboarding). You also benefit from Yoco business tools, and access to working capital.

### Support

For any technical inquiries, please follow these steps:

1. Send us the logs by navigating to WooCommerce > Status > Logs and selecting the logs that contain yoco and the date of the issue. Click View and copy the logs.
1. Use this email [ecommerce@yoco.com](mailto:ecommerce@yoco.com) and send us the logs along with your website URL.

== Installation ==

### Minimum Requirements

* WordPress version 5.0.0 or greater
* WooCommerce version 8.1.0 or greater
* PHP 7.4 or greater is required (PHP 8.0 or greater is recommended)
* MySQL 5.6 or greater, OR MariaDB version 10.1 or greater, is required

### Installation instructions

= Before you start, please make sure of the following: =

1. You have an active merchant account on [Yoco](https://www.yoco.com/za/)
1. You are using a compatible versions of WordPress, WooCommerce and PHP as a prerequisite for the plugin to work.

= Case 1: It is the first time you installed the plugin: =

1. Go to WordPress Dashboard > Plugins > Add New Plugin
1. In the search type "Yoco Payments"
1. Install the latest version of the plugin > Click Activate
1. Sign in to your Yoco Portal > Selling Online > Payment Gateway and Copy the Live & Test Secret Keys, one key at a time
1. Go back to the Plugin Settings on the website and Paste the keys, one key at a time. Make sure to only use a copy & paste approach and not type the keys by yourself as it may 1. result in incorrect keys and failed activation.
1. Click Save Changes
1. Do a Test transaction to make sure the plugin is working
1. Once you're satisfied, turn the plugin into the Live mode and you'll start getting real-time payments from your customers.

= Case 2: You're upgrading the plugin to the latest version: =

1. Go to WordPress Dashboard > Plugins > Installed Plugins > locate Yoco Payments plugin in the list
1. Click Deactivate and then Delete
1. Go to Plugins > Add New Plugin and search for Yoco – Install the latest version of the plugin. > Click Activate
1. Go to Yoco Plugin Settings and Remove the Live and Test Keys values. Make sure the input fields are empty (it’ll give you an error which is ok, as the fields has been empty)
1. Sign in to your Yoco Portal > Selling Online > Payment Gateway and Copy the Live & Test Secret Keys, one key at a time
1. Go back to the Plugin Settings on the website and Paste the keys, one key at a time. Make sure to only use a copy & paste approach and not type the keys by yourself as it may result in incorrect keys and failed activation.
1. Click Save Changes
1. Do a Test transaction to make sure the plugin is working
1. Once you're satisfied, turn the plugin into the Live mode and you'll start getting real-time payments from your customers.

### Support

For any technical inquiries, please follow these steps:

1. Send us the logs by navigating to WooCommerce > Status > Logs and selecting the logs that contain yoco and the date of the issue. Click View and copy the logs.
1. Use this email [ecommerce@yoco.com](mailto:ecommerce@yoco.com?subject=WordPress.org: ) and send us the logs along with your website URL.

== Screenshots ==

1. Showing how the Yoco Payments option will be on a website's checkout page.
2. Yoco Payments checkout page in Live mode.
3. Yoco Payments checkout page in Test mode with the test card details and instructions to perform a test payment.
4. Yoco Payments plugin settings. You can change the plugin mode to either Test or Live mode anytime.

== Frequently Asked Questions ==

= What currencies does this plugin support? =

Yoco currently accepts payments via Visa, Mastercard, American Express, and Instant EFT. All payments are made in South African Rands (ZAR).

= How do I test the plugin? =

Set the plugin to Test mode, fetch your Test Keys from the [Yoco Business Portal](https://portal.yoco.co.za/online/plugin-keys) and add these to the plugin configuration. Now try a test payment using the [test card](https://developer.yoco.com/online/resources/testing-info/#test-cards) info. Note that test transactions won’t appear in the Yoco Business Portal. Using a real credit card in Test mode will also fail. Once you’re ready, set the plugin to Live mode and switch to the Live Keys for real transactions!

= Where do I find documentation or support? =

More detailed installation notes can be found in [Yoco Gateway for WooCommerce: User Guide.](https://support.yoco.help/s/article/Yoco-for-WooCommerce) or you can [get in touch with us](https://www.yoco.com/za/contact/).

== Changelog ==

= 3.8.5 =

* Fix prevent GET request when order is missing Checkout ID.
* Bumped WP tested up to 6.8 and WC tested up to 9.8.

= 3.8.4 =

* Fix relative checkout url.

= 3.8.3 =

* Improve installation process.

= 3.8.2 =

* Fix duplicate refunds.

= 3.8.1 =

* Improved fix for duplicate payment complete notification.

= 3.8.0 =

* Add support for partial refunds.
* Fix duplicate payment complete notification.

= 3.7.1 =

* Improve payment complete implementation to allow use of actions and filters.
* WC tested up to 9.3.

= 3.7.0 =

* Update Payment Status Scheduler logic - Polling issue
* Add wp-env and e2e tests
* Telemetry webhook improvements

= 3.6.0 =

* Conditionally reset installation idempotency key.
* Extend the installation telemetry data.

= 3.5.0 =

* Add payment status polling as fallback method.
* Add simplify getting the logs to be shared with Yoco support.
* Update logging and REST response messages.
* Improve compatibility with themes (adjust icon display on checkout).
* Bumped WP tested up to 6.5 and WC tested up to 8.7.

= 3.4.0 =

* Add WooCommerce Blocks Checkout compatibility.
* Add notification and prevent loading Yoco Payment Gateway when WooCommerce is not active.
* Bumped WP tested up to 6.4 and WC tested up to 8.4.

= 3.3.2 =

* Add option to reveal API keys on settings page.
* Add version to yoco logger file name.
* Fix installation process when domain ends with /.
* Fix migration process.

= 3.3.1 =

* Fix installation spike when installation fail due to network issues.

= 3.3.0 =

* Add update scripts.
* Add admin notifications when Installation ID and Subscription secret are missing.
* Fix "Plugin doesn't have a valid header" error.

= 3.2.0 =

* Add option to change gateway title and description
* Set API secret keys fields to password type
* Fix plugin self deactivation

= 3.1.0 =

* Add High Performance Order Storage compatibility
* Add debug logging
* Fix saving settings issue
* Miscellaneous fixes and updates

= 3.0.2 =

* Hotfix for merchant decimal settings causing amount issues
* Hotfix for an issue with refunds

= 3.0.1 =

* Hotfix for textdomain issue

= 3.0.0 =

* Integrate with online checkout API
* Integrate with installation API
* Setup REST endpoints for webhooks

= 2.0.12 =

* Added support for PHP version 8

= 2.0.11 =

* Update EFT pricing

= 2.0.10 =

* Security updates

= 2.0.9 =

* Fix notice message

= 2.0.8 =

* Miscellaneous fixes and updates

= 2.0.7 =

* Fix updates to admin settings for firms with invalid keys

= 2.0.6 =

* Miscellaneous fixes and updates

= 2.0.5 =

* Updates to admin settings

= 2.0.4 =

* Updates to admin settings

= 2.0.3 =

* Differentiate card and EFT status

= 2.0.2 =

* Added EFT as a payment option

= 2.0.1 =

* Reverted to the previous name for the plugin's main file. If you have already upgraded to v2.0.0, upgrading to v2.0.1 will mean you’ll need to manually activate the plugin again. We encourage you to do this, as any upgrade from v2.0.0 will need this.

= 2.0.0 =

* Customer can save card for later use
* Integration of Yoco’s new payment APIs

= 1.53 =

* Added SVG checkout logo

= 1.52 =

* Updated checkout logo
* Improved error handling

= 1.51 =

* Improved retries for slow network conditions
* Updated branding

= 1.50 =

* Support legacy PHP 7 versions

= 1.49 =

* Better error recovery and retries
* Fix for misleading SQL error in logs
* Updated guidance and contact details

= 1.48 =

* Update to meet WP.org compliance review

= 1.47 =

* Handle transient connection errors with multiple retries
* More reliable error logging and reporting
* WordPress 5.6 test declaration

= 1.46 =

* Add WooCommerce version check support to plugin header
* Ensure order total is always consistent
* Add filter wc_yoco_popup_configuration

= 1.45 =

* Better error handling
* More useful error messages displayed to merchant
* Ensure Order status is updated correctly

= 1.44 =

* Bugfixes

= 1.43 =

* Fixed Virtual Product AutoComplete Bug

= 1.41 =

* Auto Complete Virtual Orders Variations Bugfix

= 1.40 =

* An improved payment experience that is simpler and quicker. This is the first of several improvements we will be releasing.
* Clearer error responses to give merchants better insight into failed transactions
* Automated order completion, on successful payment, for virtual or digital product orders

= 1.030 =

* Improved client error logging and Yoco client diagnostics
* Site in sub-folder fix

= 1.021 =

* Improved client error logging and Yoco client diagnostics
* Edge case rounding issue fix
* WooCommerce Notice on plugin admin page if trying to activate and WooCommerce is not active/installed

= 1.010 =

* Replaced Guzzle with Wordpress native functions
* Improved client error logging and Yoco client diagnostics
* Updated Plugin Readme.md

= 1.000 =

* Initial Release.
