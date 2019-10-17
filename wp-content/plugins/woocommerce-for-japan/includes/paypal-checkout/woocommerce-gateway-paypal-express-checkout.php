<?php
/**
 * Plugin Name: WooCommerce PayPal Checkout Gateway
 * Plugin URI: https://woocommerce.com/products/woocommerce-gateway-paypal-express-checkout/
 * Description: A payment gateway for PayPal Checkout (https://www.paypal.com/us/webapps/mpp/paypal-checkout).
 * WC tested up to: 3.7
 * WC requires at least: 2.6
 */
/**
 * Copyright (c) 2019 PayPal, Inc.
 *
 * The name of the PayPal may not be used to endorse or promote products derived from this
 * software without specific prior written permission. THIS SOFTWARE IS PROVIDED ``AS IS'' AND
 * WITHOUT ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, WITHOUT LIMITATION, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if(!class_exists('WC_Gateway_PPEC_Plugin') and get_option('wc4jp-ppec')){
    define( 'JP4WC_GATEWAY_PPEC_VERSION', '1.6.17' );

    /**
     * Return instance of WC_Gateway_PPEC_Plugin.
     *
     * @return WC_Gateway_PPEC_Plugin
     */
    function wc_gateway_ppec() {
	    static $plugin;

	    if ( ! isset( $plugin ) ) {
		    require_once( 'includes/class-wc-gateway-ppec-plugin.php' );

		    $plugin = new WC_Gateway_PPEC_Plugin( __FILE__, JP4WC_GATEWAY_PPEC_VERSION );
	    }

	    return $plugin;
    }

    wc_gateway_ppec()->maybe_run();
}
