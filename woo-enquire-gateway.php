<?php
/*
Plugin Name: Woo Enquire Gateway
Plugin URI: 
Description: This plugin adds enquiery instead of payment
Author: venqka@shtrak.eu
Version: 1.0
Author URI: shtrak.eu
*/

include( 'enquiry-gateway.php' );

function add_to_gateways() {

    function add_nop( $methods ) {

        $methods[] = 'WC_Gateway_Enquiry';
        return $methods;
    }
    add_filter( 'woocommerce_payment_gateways', 'add_nop' );     

}
add_action( 'plugins_loaded', 'add_to_gateways', 11 );