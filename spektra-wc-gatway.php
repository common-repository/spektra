<?php
/*
 * Plugin Name: Spektra
 * Plugin URI: https://spektra.co/documentation/payments/checkout
 * Description: A WooCommerce Payment Gateway. Accept Mobile Money payments instantly.
 * Author: Spektra Inc.
 * Author URI: https://spektra.co
 * Version: 1.0.1
 * WC tested up to: 4.0.1
 *
 * /*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */

	

	register_activation_hook( __FILE__, 'spektra_gateway_activation' );
	register_deactivation_hook( __FILE__, 'spektra_gateway_deactivation' );

	add_filter( 'woocommerce_payment_gateways', 'spektra_add_gateway_class' );
	function spektra_add_gateway_class( $gateways ) {
		$gateways[] = 'WC_Spektra_Gateway'; // your class name is here
		return $gateways;
	}
 
	/*
	* The class itself, made available during plugins_loaded action hook
	*/
	add_action( 'plugins_loaded', 'spektra_init_gateway_class' );

	function spektra_init_gateway_class() {
		require_once('spektra-ajax.php');
		$spektra_wc_ajax = new Spektra_WC_AJAX();
		$spektra_wc_ajax->init();

		class WC_Spektra_Gateway extends WC_Payment_Gateway {
			
			private $access_token;
			/**
			 * Class constructor, more about it in Step 3
			*/
			public function __construct() {
	
				$this->id = 'spektra'; // payment gateway plugin ID
				$this->icon = 'https://s3.us-east-2.amazonaws.com/spektrafiles/SP-WC-1-908dcbc0-4646-4735-943e-05bc4281faea.png'; // URL of the icon that will be displayed on checkout page near your gateway name
				$this->method_title = 'Spektra Gateway';
				$this->method_description = 'Spektra makes it extremely easy for businesses to accept Mobile Money payments
											 from and to multiple providers and currencies within minutes.';
				// support for simple payments
				$this->supports = array(
					'products'
				);
			
				// Method with all the options fields
				$this->init_form_fields();
			
				// Load the settings.
				$this->init_settings();
				$this->title = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );
				$this->enabled = $this->get_option( 'enabled' );
				$this->livemode = 'yes' === $this->get_option( 'livemode' );

				$this->private_key = $this->livemode ? $this->get_option( 'private_key' ) : $this->get_option( 'test_private_key' );
				$this->public_key = $this->livemode ? $this->get_option( 'public_key' ) : $this->get_option( 'test_public_key');
				
				$this->sub_account_enabled = $this->get_option( 'sub_account_enabled' ) === 'yes';
				$this->sub_account = $this->get_option( 'sub_account' );

				if ($this->livemode) {
					$this->urlprefix = 'https://api.spektra.co/';
					$this->checkoutUrl = 'https://checkout.spektra.co/';
				} else {
					$this->urlprefix = 'https://api-test.spektra.co/';
					$this->checkoutUrl = 'https://demo-checkout.spektra.co/';
				}
			
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				// add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
				add_action('woocommerce_thankyou', array($this,'spektra_payment_complete'), 10, 1);
				add_action('woocommerce_cancelled_order', array($this,'spektra_payment_cancelled'), 10, 1);
			}
	
			/**
			 * Plugin options, we deal with it in Step 3 too
			*/
			public function init_form_fields(){
				$this->form_fields = array(
					'enabled' => array(
						'title'       => 'Enable/Disable',
						'label'       => 'Enable Spektra Gateway',
						'type'        => 'checkbox',
						'description' => '',
						'default'     => 'no'
					),
					'title' => array(
						'title'       => 'Title',
						'type'        => 'text',
						'description' => 'This controls the title which the user sees during checkout.',
						'default'     => 'Mobile Money',
						'desc_tip'    => true,
					),
					'description' => array(
						'title'       => 'Description',
						'type'        => 'textarea',
						'description' => 'This controls the description which the user sees during checkout.',
						'default'     => 'Make payment using your mobile money account.',
					),
					'testline' => array(
						'description' => '<hr>',
						'type'        => 'reset',
					),
					'test_public_key' => array(
						'title'       => 'Test Public Key',
						'type'        => 'text',
						'description' => 'This is your test public key generated from your Spektra sandbox'
					),
					'test_private_key' => array(
						'title'       => 'Test Private Key',
						'type'        => 'password',
						'description' => 'This is your test private key generated from your Spektra sandbox'
					),
					'liveline' => array(
						'description' => '<hr>',
						'type'        => 'reset',
					),
					'public_key' => array(
						'title'       => 'Live Public Key',
						'type'        => 'text',
						'description' => 'This is your live public key generated from your Spektra dashboard'
					),
					'private_key' => array(
						'title'       => 'Live Private Key',
						'type'        => 'password',
						'description' => 'This is your live private key generated from your Spektra dashboard'
					),
					'sub_account_enabled' => array(
						'title'       => 'Use Sub Account',
						'label'       => 'Use Sub Account',
						'type'        => 'checkbox',
						'description' => 'If enabled, all payments will be received in your sub-account indicated below',
						'default'     => 'no'
					),
					'sub_account' => array(
						'title'       => 'Sub Account',
						'type'        => 'text',
						'description' => 'Sub Account name from your Spektra dashboard'
					),
					'livemode' => array(
						'title'       => 'Production mode',
						'label'       => 'Enable Production Mode',
						'type'        => 'checkbox',
						'description' => 'Place the checkout in production mode using live API keys.',
						'default'     => 'no',
						'desc_tip'    => true,
					),
				);
	
			}
			
			private function auth_key()
			{
				$key = $this->public_key.':'.$this->private_key;
				return base64_encode($key);
			} 

			private function get_token()
			{
				$url = $this->urlprefix . "oauth/token?grant_type=client_credentials";

				$response = wp_remote_post($url, array(
					'method'      => 'POST',
					'headers' => array(
						'Authorization' => 'Basic '. $this->auth_key(),
						'Content-Type' => 'application/json',
					),
				));

				return json_decode($response["body"]);	
			}

			private function get_checkout_id($token, $postdata)
			{
				$url = $this->urlprefix . "api/v1/checkout/initiate";

				$response = wp_remote_post($url, array(
					'method'      => 'POST',
					'headers' => array(
						'Authorization' => 'Bearer '. $token,
						'Content-Type' => 'application/json',
					),
					'body' => $postdata,
				));

				return json_decode($response["body"]);
			}
	
			public function payment_fields() {
				// ok, display some description
				if ( $this->description ) {
					echo wpautop( wp_kses_post( $this->description ) );
				}
			}
	
			/*
			* Custom CSS and JS
			*/
			public function payment_scripts() {
	
				if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
					return;
				}
			
				// if our payment gateway is disabled, we do not have to enqueue JS too
				if ( 'no' === $this->enabled ) {
					return;
				}
			
				// no reason to enqueue JavaScript if API keys are not set
				if ( empty( $this->private_key ) || empty( $this->public_key ) ) {
					return;
				}
			
				wp_enqueue_script( 'spektra_checkout_js', $this->urlprefix . 'api/v1/checkout/js' );
				wp_register_script( 'spektra_checkout', plugins_url( 'spektra.js', __FILE__ ), array( 'jquery', 'spektra_checkout_js' ) );
				wp_localize_script( 'spektra_checkout', 'spkParams', array(
					'paymentMethodID' => $this->id,
				) );
				wp_enqueue_script( 'spektra_checkout' );
				//echo "scripts enqueued";
			}

			public function process_payment( $order_id ) {
				
				if ( empty( $this->private_key ) || empty( $this->public_key ) ) {
					$error_message = " Authorization failed - keys not set";
					wc_add_notice( __('Payment error:', 'woothemes') . $error_message, 'error' );
					return;
				}

				$token = $this->get_token();
				if(!$token->access_token)
				{
					$error_message = " Authorization failed - {$token->error}";
					wc_add_notice( __('Payment error:', 'woothemes') . $error_message, 'error' );
					return;
				}
				$order = wc_get_order( $order_id );
				
				$successUrl = $this->get_return_url( $order );
				$cancelUrl = $order->get_cancel_order_url_raw();

				$order_array = array();

				foreach ($order->get_items() as $item) {
					$product_id = $item['product_id'];
					$product_instance = wc_get_product($product_id);
					$product_name = $product_instance->get_name();
					array_push($order_array, $product_name);
				}

				$order_description = implode(', ', $order_array);

				$sub_account_name = '';

				if ($this->sub_account_enabled){
					if (isset($this->sub_account) && $this->sub_account !== ''){
						$sub_account_name = $this->sub_account;
					}
				}
				
				$postdata ="{
					\"amount\":\"{$order->get_total()}\",
					\"currency\":\"{$order->get_currency()}\",
					\"customerData\": {
						\"firstName\":\"{$order->get_billing_first_name()}\",
						\"lastName\":\"{$order->get_billing_last_name()}\",
						\"phone\":\"{$order->get_billing_phone()}\"
					},
					\"description\":\"{$order_description}\",
					\"spektraAccountName\":\"{$sub_account_name}\",
					\"successURL\":\"{$successUrl}\",
					\"cancelURL\":\"{$cancelUrl}\"
				}";
				$chekout = $this->get_checkout_id($token->access_token, $postdata);
				if($chekout->checkoutID)
				{
					// Mark order as pending (we're awaiting the payment)
					$order->update_status( 'pending', __( 'Awaiting spektra payment confirmation', 'wc-gateway-spektra' ) );
					
					return array(
						'result'   => 'success',
						'checkout_id' => $chekout->checkoutID,
						'cancel_url' => $cancelUrl,
						'redirect' => $this->checkoutUrl . $chekout->checkoutID,
					);
				}

				$error_message = " Checkout failed";
				wc_add_notice( __('Payment error:', 'woothemes') . $error_message, 'error' );
				return;						
			}
			
			
			function spektra_payment_complete( $order_id ) {
				if ( ! $order_id )
					return;

				// Allow code execution only once 
				if	( ! get_post_meta( $order_id, '_spektra_payment_done', true ) ) 
				{
					// Get an instance of the WC_Order object
					$order = wc_get_order( $order_id );
					if($this->id == $order->get_payment_method()) //payment method is spektra, update order status as paid
					{
						// Flag the action as done and complete payment
						$order->update_meta_data( '_spektra_payment_done', true );
						$order->save();
						$order->payment_complete();
					}
				}
			}

			function spektra_payment_cancelled( $order_id ) {
				if ( ! $order_id )
					return;
			}
		}
	}

	function spektra_gateway_deactivation()
	{

	}

	function spektra_gateway_activation()
	{

	}
