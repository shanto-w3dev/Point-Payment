<?php
/**
 * Plugin Name: Point Payment
 * Description: A plugin to manage point-based payments.
 * Version: 1.0.0
 * Plugin URI: https://shanto.net/plugins/point-payment
 * Author: Riadujjaman Shanto
 * Author URI: https://shanto.net
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: point-payment
 * Domain Path: /languages
 * Copyright: 2024 Riadujjaman Shanto
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


// Load gateway after WooCommerce is loaded
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'WC_Payment_Gateway' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-point-payment-gateway.php';
    }
    require_once plugin_dir_path( __FILE__ ) . 'admin/class-point-payment-admin.php';
    require_once plugin_dir_path( __FILE__ ) . 'frontend/class-point-payment-frontend.php';
});


