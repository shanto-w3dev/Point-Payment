<?php
// Point Payment Gateway Class
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

// Register the gateway after plugins_loaded
add_filter( 'woocommerce_payment_gateways', function( $gateways ) {
    $gateways[] = 'Point_Payment_Gateway';
    return $gateways;
});

class Point_Payment_Gateway extends WC_Payment_Gateway {
    public $conversion_rate;
    public function __construct() {
        $this->id                 = 'point_payment';
        $this->icon               = '';
        $this->has_fields         = false;
        $this->method_title       = __( 'Point Payment', 'point-payment' );
        $this->method_description = __( 'Allow customers to pay using points.', 'point-payment' );

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
        $this->conversion_rate = $this->get_option( 'conversion_rate', 1 );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'point-payment' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Point Payment', 'point-payment' ),
                'default' => 'no',
            ),
            'title' => array(
                'title'       => __( 'Title', 'point-payment' ),
                'type'        => 'text',
                'description' => __( 'Title shown at checkout.', 'point-payment' ),
                'default'     => __( 'Pay with Points', 'point-payment' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Description', 'point-payment' ),
                'type'        => 'textarea',
                'description' => __( 'Description shown at checkout.', 'point-payment' ),
                'default'     => __( 'Use your points to pay for this order.', 'point-payment' ),
            ),
            'conversion_rate' => array(
                'title'       => __( 'Point Conversion Rate', 'point-payment' ),
                'type'        => 'number',
                'description' => __( 'How much is 1 point worth in your store currency?', 'point-payment' ),
                'default'     => 1,
                'desc_tip'    => true,
                'custom_attributes' => array('min' => 0.01, 'step' => 0.01),
            ),
        );
    }

    // Only restrict if gateway is disabled
    public function is_available() {
        $enabled = $this->get_option( 'enabled' );
        if ( $enabled !== 'yes' ) return false;
        return true;
    }

    // Show point info on checkout
    public function payment_fields() {
        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $points = (int) get_user_meta( $user_id, 'point_payment_points', true );
            $conversion = floatval( $this->conversion_rate );
            if ( $conversion <= 0 ) $conversion = 1;
            $cart_total = WC()->cart ? WC()->cart->total : 0;
            $needed_points = ceil( $cart_total / $conversion );
            echo '<p>' . sprintf( __( 'You have %d points. This order needs %d points. (1 point = %s)', 'point-payment' ), $points, $needed_points, wc_price( $conversion ) ) . '</p>';
        }
    }

    // Process the payment
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        $user_id = $order->get_user_id();
        $conversion = floatval( $this->conversion_rate );
        if ( $conversion <= 0 ) $conversion = 1;
        $total = $order->get_total();
        $needed_points = ceil( $total / $conversion );
        $points = (int) get_user_meta( $user_id, 'point_payment_points', true );
        if ( $points < $needed_points ) {
            wc_add_notice( __( 'Not enough points.', 'point-payment' ), 'error' );
            return array( 'result' => 'failure' );
        }
        // Deduct points
        update_user_meta( $user_id, 'point_payment_points', $points - $needed_points );
        // Add to point history
        $history = get_user_meta( $user_id, 'point_payment_history', true );
        if ( ! is_array( $history ) ) $history = array();
        $history[] = array(
            'type' => 'debit',
            'points' => $needed_points,
            'order_id' => $order_id,
            'date' => current_time( 'mysql' ),
        );
        update_user_meta( $user_id, 'point_payment_history', $history );
        $order->payment_complete();
        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        );
    }
}


