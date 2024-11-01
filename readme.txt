=== Spektra ===
Contributors: spektra
Donate link: https://spektra.co/
Tags: payments, checkout
Requires at least: 4.6
Tested up to: 5.4
Stable tag: 1.0
Requires PHP: 5.2

A WooCommerce Payment Gateway. Accept Mobile Money payments instantly.


== Description ==

Spektra allows you to accept Mobile Money payments in multiple currencies in your WooCommerce shop.
It is the fastest Mobile Money checkout which works on the web, mobile and in-app, with a real-time dashboard so you can visualize your transactions.

Spektra is currently available in Ghana and Kenya, and supports the following providers, M-PESA, MTN Mobile Money, AirtelTigo Money, and Vodafone Cash.


The Checkout has the following features;

    * Cross-Border Payments: Allows you to accept payments from multiple currencies without additional coding work.

    * Dynamic Currency Conversion: This allows your customers to see and pay you in their prefered currency.

    * Geolocation: This makes it possible for you to know the location of your customers, a security feature to curb fraud.

    * Payment Switch: This automatically detects the provider of your customers and forwards the payment request accordingly.


Check out https://spektra.co/documentation/payments/checkout for more information about Spektra checkout.


== Installation ==

1. Download the plugin and place the folder in WordPress plugins folder (wp-content/plugins).
2. Go to to the plugins page from your WordPress admin dashboard.
3. After installing and activating the plugin go to WooCommerce > Settings on your admin dashboard.
4. Choose the \"payments\" tab and enable the payment gateway. You should see \"Spektra\" in the list of methods.
5. Click on \"Set up\" button and enter the public and private keys obtained from your Spektra dashboard.
6. Payment gateway is setup and ready to be used.

== Frequently Asked Questions ==

= What is Spektra? =
Spektra is a WooCommerce Payment Gateway which allows you to accept Mobile Money payments from multiple currencies within minutes, NOT months.

== Screenshots ==

1. Woocommerce payment Settings
2. Spektra set up credentials
3. Cart checkout
4. Confirm and place order
5. Spektra Checkout form
6. Successful payment
7. Payment receipt

== Changelog ==

= 1.0.1 =
* Initial version.

= 1.0.2 =
* Updated checkout

= 1.0.3 =
* Prefill customer data from billing details

= 1.0.4 =
* Added sub accounts to settings

== Upgrade Notice ==

= 1.0.1 =
No upgrade needed, this is the initial version

= 1.0.1 =
Update checkout page

= 1.0.1 =
Prefill customer data from billing details

= 1.0.1 =
Receive Payments in Sub Account


== Arbitrary section ==

This plugin sends requests to https://api.spektra.co for production mode and https://api-test.spektra.co for test mode, for authentication and obtaining the checkout ID.
The response received redirects the user to https://checkout.spektra.co/{checkout-id} for production mode and https://demo-checkout.spektra.co/{checkout-id} for test mode.
The user completes the payment from this page.


